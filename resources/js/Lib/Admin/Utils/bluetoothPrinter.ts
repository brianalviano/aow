import ReceiptPrinterEncoder from '@point-of-sale/receipt-printer-encoder';

type BluetoothDevice = any;
type BluetoothRemoteGATTCharacteristic = any;

// Common service UUIDs advertised by bluetooth thermal printers
export const PRINTER_SERVICE_UUIDS = [
    '000018f0-0000-1000-8000-00805f9b34fb',
    '0000ff00-0000-1000-8000-00805f9b34fb',
    '0000e0e1-0000-1000-8000-00805f9b34fb',
    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
    '00001101-0000-1000-8000-00805f9b34fb'
];

interface BluetoothState {
    device: BluetoothDevice | null;
    characteristic: BluetoothRemoteGATTCharacteristic | null;
    isConnected: boolean;
    name: string;
}

// Global in-memory state for the active Bluetooth printer connection
let printerState: BluetoothState = {
    device: null,
    characteristic: null,
    isConnected: false,
    name: ''
};

export const bluetoothPrinterManager = {
    getState() {
        return printerState;
    },

    async getPairedDevices(): Promise<BluetoothDevice[]> {
        if ((navigator as any).bluetooth && typeof (navigator as any).bluetooth.getDevices === 'function') {
            try {
                return await (navigator as any).bluetooth.getDevices();
            } catch (e) {
                console.error("Error getting paired devices:", e);
            }
        }
        return [];
    },

    async connectDevice(device: BluetoothDevice): Promise<boolean> {
        try {
            console.log(`Connecting to ${device.name}...`);
            const server = await device.gatt.connect();
            
            let writeChar: BluetoothRemoteGATTCharacteristic | null = null;
            
            for (const uuid of PRINTER_SERVICE_UUIDS) {
                try {
                    const service = await server.getPrimaryService(uuid);
                    const characteristics = await service.getCharacteristics();
                    writeChar = (characteristics as any[]).find(
                        (c: any) => c.properties.write || c.properties.writeWithoutResponse
                    ) || null;
                    
                    if (writeChar) {
                        console.log(`Found printer characteristic under service: ${uuid}`);
                        break;
                    }
                } catch (e) {
                    // Try next service
                }
            }

            if (!writeChar) {
                throw new Error("Could not find writable printer characteristic");
            }

            // Hook up disconnect listener
            device.addEventListener('gattserverdisconnected', () => {
                console.log("GATT Server disconnected");
                printerState = {
                    device: null,
                    characteristic: null,
                    isConnected: false,
                    name: ''
                };
            });

            printerState = {
                device,
                characteristic: writeChar,
                isConnected: true,
                name: device.name || 'Printer POS'
            };
            
            localStorage.setItem('pos_last_printer_name', printerState.name);
            return true;
        } catch (err) {
            console.error("GATT connection failed:", err);
            return false;
        }
    },

    async requestAndConnect(): Promise<boolean> {
        if (!(navigator as any).bluetooth) {
            throw new Error("Browser Anda tidak mendukung Web Bluetooth (memerlukan HTTPS/localhost)");
        }

        try {
            const device = await (navigator as any).bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: PRINTER_SERVICE_UUIDS
            });

            return await this.connectDevice(device);
        } catch (err) {
            console.error("Bluetooth device request failed:", err);
            throw err;
        }
    },

    async autoConnectLast(): Promise<boolean> {
        try {
            const devices = await this.getPairedDevices();
            const lastName = localStorage.getItem('pos_last_printer_name');
            if (devices.length > 0 && lastName) {
                const lastDevice = devices.find(d => d.name === lastName) || devices[0];
                if (lastDevice) {
                    return await this.connectDevice(lastDevice);
                }
            }
        } catch (err) {
            console.warn("Auto-connect failed:", err);
        }
        return false;
    },

    async disconnect() {
        if (printerState.device && printerState.device.gatt.connected) {
            printerState.device.gatt.disconnect();
        }
        printerState = {
            device: null,
            characteristic: null,
            isConnected: false,
            name: ''
        };
    },

    async write(data: Uint8Array) {
        if (!printerState.characteristic) {
            throw new Error("Printer tidak terhubung.");
        }

        const CHUNK_SIZE = 40; // LE write chunk size
        const char = printerState.characteristic;

        for (let i = 0; i < data.length; i += CHUNK_SIZE) {
            const chunk = data.slice(i, i + CHUNK_SIZE);
            if (char.properties.writeWithoutResponse) {
                await char.writeValueWithoutResponse(chunk);
            } else {
                await char.writeValue(chunk);
            }
            // Safe delay for buffer flushing on physical printers
            await new Promise(resolve => setTimeout(resolve, 8));
        }
    },

    formatRupiah(value: number): string {
        return 'Rp ' + Math.floor(value).toLocaleString('id-ID');
    },

    formatDateTime(dateStr: string): string {
        try {
            const date = new Date(dateStr);
            const d = String(date.getDate()).padStart(2, '0');
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const y = date.getFullYear();
            const h = String(date.getHours()).padStart(2, '0');
            const i = String(date.getMinutes()).padStart(2, '0');
            return `${d}/${m}/${y} ${h}.${i}`;
        } catch {
            return dateStr;
        }
    },

    formatDeliveryDate(dateStr: string): string {
        if (!dateStr) return '-';
        const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        try {
            const date = new Date(dateStr);
            const d = date.getDate();
            const m = MONTHS_ID[date.getMonth()];
            const y = date.getFullYear();
            return `${d} ${m} ${y}`;
        } catch {
            return dateStr;
        }
    },

    generateEscPosBytes(
        order: any,
        settings: any,
        type: 'all' | 'checker' | 'invoice',
        width: '58mm' | '80mm'
    ): Uint8Array {
        const colWidth = width === '80mm' ? 48 : 32;
        const encoder = new ReceiptPrinterEncoder({
            width: colWidth
        });
        
        encoder.initialize();

        const companyName = settings?.name || 'AOWENAK';
        const nowStr = this.formatDateTime(new Date().toISOString());

        // SECTION 1: KITCHEN CHECKER
        if (type === 'all' || type === 'checker') {
            encoder
                .align('center')
                .bold(true)
                .size('double')
                .line(companyName)
                .size('normal')
                .bold(false)
                .line("CHECKER DAPUR")
                .rule({ char: '=' })
                
                .align('left')
                .line(`No. Pesanan: #${order.number}`);
            
            const delDate = this.formatDeliveryDate(order.delivery_date);
            const delTime = order.delivery_time ? `@ ${order.delivery_time}` : '';
            encoder.line(`Tgl Kirim: ${delDate} ${delTime}`);
            
            const shippingType = order.shipping_method === 'instant' ? 'Instant Delivery' : 'Pre-order';
            encoder.line(`Tipe: ${shippingType}`);
            encoder.line(`Pelanggan: ${order.customer?.name || '-'}`);
            
            const dropPointName = order.drop_point?.name || order.customer_address?.address || 'Ambil Sendiri';
            encoder.line(`Drop Point: ${dropPointName}`);
            
            if (order.school_class) {
                encoder.line(`Kelas: ${order.school_class}`);
            }
            
            if (order.note) {
                encoder
                    .rule({ char: '-' })
                    .bold(true)
                    .line("Catatan Order:")
                    .bold(false)
                    .line(order.note);
            }
            
            encoder
                .rule({ char: '=' })
                .bold(true);
            
            const itemColWidth = colWidth - 5;
            encoder.table([
                { width: 5, align: 'left' },
                { width: itemColWidth, align: 'left' }
            ], [
                ['Qty', 'Item Menu & Pilihan']
            ]);
            
            encoder.bold(false).rule({ char: '-' });
            
            if (order.items && order.items.length > 0) {
                for (const item of order.items) {
                    const optionLines: string[] = [];
                    if (item.options && item.options.length > 0) {
                        const optNames = item.options
                            .map((o: any) => `${o.product_option?.name || ''}: ${o.product_option_item?.name || ''}`)
                            .join(', ');
                        optionLines.push(`- ${optNames}`);
                    }
                    if (item.note) {
                        optionLines.push(`* Catatan: ${item.note}`);
                    }
                    
                    let itemDesc = item.product?.name || '-';
                    if (optionLines.length > 0) {
                        itemDesc += '\n' + optionLines.join('\n');
                    }
                    
                    encoder.table([
                        { width: 5, align: 'left' },
                        { width: itemColWidth, align: 'left' }
                    ], [
                        [`${item.quantity}x`, itemDesc]
                    ]);
                    encoder.newline();
                }
            }
            
            encoder
                .rule({ char: '-' })
                .align('center')
                .line(`Dicetak: ${nowStr}`)
                .feed(3);
        }

        // SECTION 2: CUSTOMER INVOICE
        if (type === 'all' || type === 'invoice') {
            encoder
                .align('center')
                .bold(true)
                .size('double')
                .line(companyName)
                .size('normal')
                .bold(false);
            
            if (settings?.address) {
                encoder.line(settings.address);
            }
            const phoneStr = settings?.whatsapp || settings?.phone || '';
            if (phoneStr) {
                encoder.line(`Telp/WA: ${phoneStr}`);
            }
            
            encoder
                .rule({ char: '-' })
                .bold(true)
                .line("STRUK PEMBAYARAN CUSTOMER")
                .bold(false)
                .rule({ char: '=' })
                
                .align('left')
                .line(`No. Pesanan: #${order.number}`);
            
            const orderDate = this.formatDateTime(order.created_at);
            encoder.line(`Tanggal: ${orderDate}`);
            
            const delDate = this.formatDeliveryDate(order.delivery_date);
            const delTime = order.delivery_time ? `@ ${order.delivery_time}` : '';
            encoder.line(`Kirim Pada: ${delDate} ${delTime}`);
            
            const custPhone = order.customer?.phone ? ` (${order.customer.phone})` : '';
            encoder.line(`Pelanggan: ${(order.customer?.name || '-') + custPhone}`);
            
            const dropPointName = order.drop_point?.name || order.customer_address?.address || 'Ambil Sendiri';
            encoder.line(`Drop Point: ${dropPointName}`);
            
            if (order.school_class) {
                encoder.line(`Kelas: ${order.school_class}`);
            }
            
            encoder
                .rule({ char: '=' })
                .bold(true);
            
            const priceColWidth = 12;
            const nameColWidth = colWidth - priceColWidth;
            
            encoder.table([
                { width: nameColWidth, align: 'left' },
                { width: priceColWidth, align: 'right' }
            ], [
                ['Menu & Pilihan', 'Total']
            ]);
            
            encoder.bold(false).rule({ char: '-' });
            
            let itemSubtotal = 0;
            if (order.items && order.items.length > 0) {
                for (const item of order.items) {
                    const unitPrice = item.quantity > 0 ? (item.subtotal / item.quantity) : 0;
                    const priceDetails = `${item.quantity} x ${this.formatRupiah(unitPrice)}`;
                    
                    let itemDesc = item.product?.name || '-';
                    if (item.options && item.options.length > 0) {
                        const optNames = item.options
                            .map((o: any) => `${o.product_option?.name || ''}: ${o.product_option_item?.name || ''}`)
                            .join(', ');
                        itemDesc += `\n  ${optNames}`;
                    }
                    itemDesc += `\n  ${priceDetails}`;
                    
                    encoder.table([
                        { width: nameColWidth, align: 'left' },
                        { width: priceColWidth, align: 'right' }
                    ], [
                        [itemDesc, this.formatRupiah(item.subtotal)]
                    ]);
                    
                    itemSubtotal += item.subtotal;
                }
            }
            
            encoder.rule({ char: '-' });
            
            const addTotalRow = (label: string, value: string, isBold: boolean = false) => {
                encoder.bold(isBold).table([
                    { width: nameColWidth, align: 'left' },
                    { width: priceColWidth, align: 'right' }
                ], [
                    [label, value]
                ]).bold(false);
            };
            
            addTotalRow("Total Item:", this.formatRupiah(itemSubtotal));
            
            if (order.discount_amount > 0) {
                addTotalRow("Diskon:", `-${this.formatRupiah(order.discount_amount)}`);
            }
            if (order.delivery_fee > 0) {
                addTotalRow("Ongkos Kirim:", this.formatRupiah(order.delivery_fee));
            }
            if (order.admin_fee > 0) {
                addTotalRow("Biaya Admin:", this.formatRupiah(order.admin_fee));
            }
            if (order.service_fee > 0) {
                addTotalRow("Biaya Layanan:", this.formatRupiah(order.service_fee));
            }
            if (order.tax_amount > 0) {
                addTotalRow("Pajak:", this.formatRupiah(order.tax_amount));
            }
            
            encoder.rule({ char: '-' });
            addTotalRow("TOTAL BAYAR:", this.formatRupiah(order.total_amount), true);
            
            encoder
                .rule({ char: '=' })
                .align('center')
                .line(`Metode: ${order.payment_method?.name || '-'}`)
                .bold(true);
                
            const isPaid = order.payment_status === 'paid' ? 'LUNAS' : 'BELUM BAYAR';
            encoder.line(`STATUS: ${isPaid}`)
                .bold(false)
                .newline()
                .line("Terima kasih atas pesanan Anda!")
                .feed(4);
        }

        return encoder.encode();
    },

    async printOrder(
        order: any,
        settings: any,
        type: 'all' | 'checker' | 'invoice',
        width: '58mm' | '80mm'
    ): Promise<boolean> {
        try {
            const bytes = this.generateEscPosBytes(order, settings, type, width);
            await this.write(bytes);
            return true;
        } catch (e) {
            console.error("Print order failed:", e);
            throw e;
        }
    },

    async printTestReceipt(width: '58mm' | '80mm'): Promise<boolean> {
        try {
            const colWidth = width === '80mm' ? 48 : 32;
            const encoder = new ReceiptPrinterEncoder({
                width: colWidth
            });
            encoder.initialize();
            
            encoder
                .align('center')
                .bold(true)
                .size('double')
                .line("TEST PRINTER")
                .size('normal')
                .bold(false)
                .line("Koneksi Bluetooth Berhasil!")
                .rule({ char: '-' })
                
                .align('left')
                .line(`Ukuran Kertas: ${width}`)
                .line(`Lebar Kolom: ${colWidth} Karakter`)
                .line(`Waktu: ${this.formatDateTime(new Date().toISOString())}`)
                
                .rule({ char: '=' })
                .align('center')
                .line("AOWENAK POS PRINTER")
                .feed(4);
            
            const bytes = encoder.encode();
            await this.write(bytes);
            return true;
        } catch (e) {
            console.error("Test print failed:", e);
            throw e;
        }
    }
};

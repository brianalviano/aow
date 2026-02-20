export type ShiftLite =
    | {
          end_time: string | null;
          is_overnight: boolean;
      }
    | null
    | undefined;

export function canCheckout(
    date: string | null | undefined,
    shift: ShiftLite
): boolean {
    if (!date || !shift || !shift.end_time) return true;
    const end = new Date(`${date}T${shift.end_time}`);
    if (Number.isNaN(end.getTime())) return true;
    if (shift.is_overnight) {
        end.setDate(end.getDate() + 1);
    }
    const allowedStart = new Date(end.getTime() - 15 * 60000);
    return Date.now() >= allowedStart.getTime();
}

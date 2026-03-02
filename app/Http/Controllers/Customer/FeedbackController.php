<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\DTOs\Customer\FeedbackDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreFeedbackRequest;
use App\Services\FeedbackService;
use Illuminate\Support\Facades\Auth;
use Inertia\{Inertia, Response};
use Illuminate\Http\RedirectResponse;

/**
 * Controller for handling customer feedback.
 */
class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService
    ) {}

    /**
     * Display the feedback form.
     *
     * @return Response
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Customer/Feedback/Index');
    }

    /**
     * Store a new feedback.
     *
     * @param StoreFeedbackRequest $request
     * @return RedirectResponse
     */
    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        /** @var \App\Models\Customer $user */
        $user = Auth::guard('customer')->user();

        $dto = new FeedbackDTO(
            customerId: (string) $user->id,
            type: $request->validated('type'),
            content: $request->validated('content')
        );

        $this->feedbackService->store($dto);

        return redirect()->back()->with('success', 'Terima kasih atas kritik dan sarannya!');
    }
}

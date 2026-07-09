<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Notifications\NotificationManager;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(Request $request): Response
    {
        $promotions = Promotion::query()
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Promotion $promotion): array => $this->row($promotion))
            ->toArray();

        return Inertia::render('LineWatt/Promotions', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'promotions' => $promotions,
            'plans' => ['all' => 'All Plans', 'subscriber' => 'Subscriber', 'pro' => 'Manufacturer Pro', 'enterprise' => 'Manufacturer Enterprise'],
            'discountTypes' => ['percent' => 'Percent', 'fixed' => 'Fixed', 'trial_extension' => 'Trial Extension', 'custom' => 'Custom'],
            'statuses' => ['draft' => 'Draft', 'active' => 'Active', 'paused' => 'Paused', 'expired' => 'Expired', 'archived' => 'Archived'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $promotion = Promotion::create([
            ...$data,
            'metadata' => [
                'notes' => $data['notes'] ?? null,
                'paddle_integration' => 'pending',
            ],
        ]);

        $activity = app(ActivityLogger::class)->log('PromotionCreated', $request->user(), $promotion, [
            'promotion_id' => $promotion->id,
            'code' => $promotion->code,
        ]);

        app(NotificationManager::class)->notifyLibrarians(
            $promotion->status === 'active' ? 'PromotionActivated' : 'PromotionCreated',
            $promotion->status === 'active' ? 'Promotion activated' : 'Promotion created',
            $promotion->code.' is stored in LineWatt Library. Paddle integration remains pending.',
            route('admin.library.promotions'),
            $activity,
        );

        return back()->with('success', 'Promotion created. Paddle integration remains pending.');
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validated($request, $promotion);

        $promotion->fill([
            ...$data,
            'metadata' => [
                ...($promotion->metadata ?? []),
                'notes' => $data['notes'] ?? null,
            ],
        ])->save();

        app(ActivityLogger::class)->log('PromotionUpdated', $request->user(), $promotion, [
            'promotion_id' => $promotion->id,
            'code' => $promotion->code,
        ]);

        return back()->with('success', 'Promotion updated.');
    }

    public function pause(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->forceFill(['status' => 'paused'])->save();
        app(ActivityLogger::class)->log('PromotionPaused', $request->user(), $promotion, ['promotion_id' => $promotion->id]);

        return back()->with('success', 'Promotion paused.');
    }

    public function archive(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->forceFill(['status' => 'archived'])->save();
        app(ActivityLogger::class)->log('PromotionArchived', $request->user(), $promotion, ['promotion_id' => $promotion->id]);

        return back()->with('success', 'Promotion archived.');
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request, ?Promotion $promotion = null): array
    {
        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        return $request->validate([
            'code' => ['required', 'string', 'max:80', 'unique:promotions,code,'.($promotion?->id ?? 'NULL').',id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'discount_type' => ['required', 'in:percent,fixed,trial_extension,custom'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'applies_to_plan' => ['required', 'in:subscriber,pro,enterprise,all'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:draft,active,paused,expired,archived'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function row(Promotion $promotion): array
    {
        return [
            'id' => $promotion->id,
            'uuid' => $promotion->uuid,
            'code' => $promotion->code,
            'title' => $promotion->title,
            'description' => $promotion->description,
            'discount_type' => $promotion->discount_type,
            'discount_value' => $promotion->discount_value,
            'applies_to_plan' => $promotion->applies_to_plan,
            'starts_at' => $promotion->starts_at?->toDateString(),
            'ends_at' => $promotion->ends_at?->toDateString(),
            'max_redemptions' => $promotion->max_redemptions,
            'redemption_count' => $promotion->redemption_count,
            'status' => $promotion->status,
            'paddle_coupon_id' => $promotion->paddle_coupon_id,
            'notes' => $promotion->metadata['notes'] ?? null,
            'routes' => [
                'update' => route('admin.library.promotions.update', ['promotion' => $promotion]),
                'pause' => route('admin.library.promotions.pause', ['promotion' => $promotion]),
                'archive' => route('admin.library.promotions.archive', ['promotion' => $promotion]),
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAnnouncement;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCustomerAnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index', [
            'announcements' => CustomerAnnouncement::query()
                ->withCount(['stores', 'customers', 'dismissals'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function create()
    {
        return view('admin.announcements.form', $this->formData(new CustomerAnnouncement));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $announcement = CustomerAnnouncement::query()->create($this->payload($data, $request));
        $this->syncAudience($announcement, $data);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement created.');
    }

    public function edit(CustomerAnnouncement $announcement)
    {
        $announcement->load(['stores', 'customers']);

        return view('admin.announcements.form', $this->formData($announcement));
    }

    public function update(Request $request, CustomerAnnouncement $announcement)
    {
        $data = $this->validated($request);
        $announcement->update($this->payload($data, $request, $announcement));
        $this->syncAudience($announcement, $data);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(CustomerAnnouncement $announcement)
    {
        $announcement->update([
            'enabled' => false,
            'inactive_since' => $announcement->inactive_since ?? now('Asia/Kolkata'),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deactivated.');
    }

    private function formData(CustomerAnnouncement $announcement): array
    {
        return [
            'announcement' => $announcement,
            'stores' => StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get(),
            'customers' => Customer::query()->where('status', true)->orderBy('name')->limit(500)->get(),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
            'audience_type' => ['required', Rule::in(['all', 'stores', 'customers'])],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', 'exists:stock_locations,id'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],
            'sticky' => ['nullable', 'boolean'],
            'dismissible' => ['nullable', 'boolean'],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'enabled' => ['nullable', 'boolean'],
        ]);
    }

    private function payload(array $data, Request $request, ?CustomerAnnouncement $announcement = null): array
    {
        $enabled = (bool) ($data['enabled'] ?? false);

        return [
            'title' => $data['title'] ?? null,
            'message' => $data['message'],
            'audience_type' => $data['audience_type'],
            'sticky' => (bool) ($data['sticky'] ?? false),
            'dismissible' => (bool) ($data['dismissible'] ?? false),
            'priority' => (int) $data['priority'],
            'cta_text' => $data['cta_text'] ?? null,
            'cta_url' => $data['cta_url'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'enabled' => $enabled,
            'inactive_since' => $enabled ? null : ($announcement?->inactive_since ?? now('Asia/Kolkata')),
            'created_by' => $announcement?->created_by ?? $request->user()?->id,
        ];
    }

    private function syncAudience(CustomerAnnouncement $announcement, array $data): void
    {
        $announcement->stores()->sync($data['audience_type'] === 'stores' ? array_values(array_unique($data['store_ids'] ?? [])) : []);
        $announcement->customers()->sync($data['audience_type'] === 'customers' ? array_values(array_unique($data['customer_ids'] ?? [])) : []);
    }

    private function safeUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || str_starts_with((string) $value, '/')) {
                return;
            }

            if (! in_array(parse_url((string) $value, PHP_URL_SCHEME), ['http', 'https'], true)) {
                $fail('The '.$attribute.' must be a relative URL or an http/https URL.');
            }
        };
    }
}

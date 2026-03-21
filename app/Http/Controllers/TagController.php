<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\TicketCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        return view('admin.tags.index', [
            'ticketCategories' => TicketCategory::query()->orderBy('name')->get(),
            'tagTypes' => Tag::typeLabels(),
            'tagsByType' => Tag::query()
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->groupBy('type'),
        ]);
    }

    public function storeTicketCategory(Request $request): RedirectResponse
    {
        $category = TicketCategory::create($this->validatedTicketCategoryData($request));

        return redirect()
            ->route('admin.tags.index')
            ->with('status', "Đã thêm loại ticket {$category->name}.");
    }

    public function updateTicketCategory(Request $request, TicketCategory $ticketCategory): RedirectResponse
    {
        $ticketCategory->update($this->validatedTicketCategoryData($request, $ticketCategory));

        return redirect()
            ->route('admin.tags.index')
            ->with('status', "Đã cập nhật loại ticket {$ticketCategory->name}.");
    }

    public function storeTag(Request $request): RedirectResponse
    {
        $tag = Tag::create($this->validatedTagData($request));

        return redirect()
            ->route('admin.tags.index')
            ->with('status', "Đã thêm tag {$tag->name}.");
    }

    public function updateTag(Request $request, Tag $tag): RedirectResponse
    {
        $tag->update($this->validatedTagData($request, $tag));

        return redirect()
            ->route('admin.tags.index')
            ->with('status', "Đã cập nhật tag {$tag->name}.");
    }

    public function destroyTicketCategory(TicketCategory $ticketCategory): RedirectResponse
    {
        if ($ticketCategory->tickets()->exists()) {
            return back()->withErrors([
                'ticket_category' => 'Không thể xóa loại ticket đang được dùng bởi ticket hiện có.',
            ]);
        }

        $name = $ticketCategory->name;
        $ticketCategory->delete();

        return redirect()
            ->route('admin.tags.index')
            ->with('status', "Đã xóa loại ticket {$name}.");
    }

    public function destroyTag(Tag $tag): RedirectResponse
    {
        $name = $tag->name;
        $tag->delete();

        return redirect()
            ->route('admin.tags.index')
            ->with('status', "Đã xóa tag {$name}.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTicketCategoryData(Request $request, ?TicketCategory $ticketCategory = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [], [
            'name' => 'tên loại ticket',
        ]);

        $validated['code'] = $this->uniqueSlugForTicketCategory($validated['name'], $ticketCategory);
        $validated['is_active'] = true;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedTagData(Request $request, ?Tag $tag = null): array
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(Tag::types())],
            'name' => ['required', 'string', 'max:255'],
        ], [], [
            'type' => 'nhóm tag',
            'name' => 'tên tag',
        ]);

        $validated['code'] = $this->uniqueSlugForTag($validated['type'], $validated['name'], $tag);
        $validated['is_active'] = true;

        return $validated;
    }

    private function uniqueSlugForTicketCategory(string $name, ?TicketCategory $ticketCategory = null): string
    {
        $base = Str::slug($name);
        $root = $base !== '' ? $base : 'tag';
        $slug = $root;
        $counter = 1;

        while (TicketCategory::query()
            ->where('code', $slug)
            ->when($ticketCategory, fn ($query) => $query->where('id', '!=', $ticketCategory->id))
            ->exists()) {
            $slug = "{$root}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function uniqueSlugForTag(string $type, string $name, ?Tag $tag = null): string
    {
        $base = Str::slug($name);
        $root = $base !== '' ? $base : 'tag';
        $slug = $root;
        $counter = 1;

        while (Tag::query()
            ->where('type', $type)
            ->where('code', $slug)
            ->when($tag, fn ($query) => $query->where('id', '!=', $tag->id))
            ->exists()) {
            $slug = "{$root}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}

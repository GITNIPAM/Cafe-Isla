<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\IslandSeries;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorefrontController extends Controller
{
    public function home(): View
    {
        $islands = IslandSeries::with(['products' => function ($q) {
            $q->where('is_archived', false)
                ->with(['variants' => fn ($vq) => $vq->where('is_active', true), 'category'])
                ->orderBy('id');
        }])->orderBy('sort_order')->get();

        $merchProducts = Product::where('is_archived', false)
            ->where('is_perishable', false)
            ->with(['islandSeries', 'category', 'variants' => fn ($vq) => $vq->where('is_active', true)])
            ->orderBy('id')
            ->get();

        return view('storefront.home', [
            'islands' => $islands,
            'merchProducts' => $merchProducts,
        ]);
    }

    public function menu(Request $request): View
    {
        $islands = IslandSeries::orderBy('sort_order')->get();
        $categories = Category::where('is_archived', false)->orderBy('sort_order')->get();

        $query = Product::with(['islandSeries', 'category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_archived', false);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('short_excerpt', 'like', '%'.$search.'%')
                    ->orWhere('tasting_notes', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($islandSlug = $request->query('island')) {
            $query->whereHas('islandSeries', fn ($q) => $q->where('slug', $islandSlug));
        }

        if ($categorySlug = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($type = $request->query('type')) {
            if ($type === 'drinks') {
                $query->where('is_perishable', true);
            } elseif ($type === 'merch') {
                $query->where('is_perishable', false);
            }
        }

        if ($serveTemp = $request->query('serve_temp')) {
            if (in_array($serveTemp, ['hot', 'iced'], true)) {
                $query->whereHas('variants', fn ($q) => $q->where('serve_temp', $serveTemp)->where('is_active', true));
            }
        }

        if ($maxPrice = $request->query('max_price')) {
            if (is_numeric($maxPrice) && (float) $maxPrice > 0) {
                $query->where('base_price', '<=', (float) $maxPrice);
            }
        }

        $sort = $request->query('sort', 'series');
        match ($sort) {
            'price_asc' => $query->orderBy('base_price', 'asc'),
            'price_desc' => $query->orderBy('base_price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            default => $query->orderBy('island_series_id', 'asc')->orderBy('id', 'asc'),
        };

        $products = $query->get();

        return view('storefront.menu', [
            'islands' => $islands,
            'categories' => $categories,
            'products' => $products,
            'filters' => $request->only(['q', 'island', 'category', 'type', 'serve_temp', 'max_price', 'sort']),
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $product = Product::with(['islandSeries', 'category', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->where('is_archived', false)
            ->first();

        if (! $product) {
            $replacement = [
                'muni-matcha' => 'urbiztondo-morning-matcha',
                'dulce-matcha' => 'monaliza-caramel-matcha',
                'isla-matcha' => 'carille-coco-matcha',
                'berry-matcha' => 'tangadan-berry-matcha',
                'spanish-hojicha' => 'san-juan-spanish-hojicha',
            ][$slug] ?? null;

            abort_unless($replacement && Product::where('slug', $replacement)->where('is_archived', false)->exists(), 404);

            return redirect()->route('product.show', $replacement, 301);
        }

        $related = Product::with(['islandSeries', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('is_archived', false)
            ->where('id', '!=', $product->id)
            ->where('island_series_id', $product->island_series_id)
            ->limit(4)
            ->get();

        return view('storefront.product', [
            'product' => $product,
            'related' => $related,
        ]);
    }

    public function track(Request $request): View
    {
        $orderNumber = trim((string) $request->query('order', ''));
        $lookup = strtoupper(ltrim($orderNumber, '#'));
        $order = null;

        if ($lookup !== '') {
            $order = $request->user()->orders()
                ->with(['items', 'payment', 'delivery.courier', 'returnRequests'])
                ->where(function ($query) use ($lookup) {
                    $query->whereRaw('UPPER(order_number) = ?', [$lookup])
                        ->orWhereHas('delivery', fn ($q) => $q->whereRaw('UPPER(tracking_number) = ?', [$lookup]));
                })
                ->first();
        }

        return view('storefront.track', [
            'orderNumber' => $orderNumber,
            'order' => $order,
        ]);
    }

    public function productImage(string $filename): BinaryFileResponse
    {
        $disk = Storage::disk('catalog_uploads');
        abort_unless($disk->fileExists($filename), 404);

        return response()->file($disk->path($filename), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function diagrams(): View
    {
        abort_unless(config('cafeisla.demo_mode'), 404);

        return view('storefront.diagrams');
    }
}

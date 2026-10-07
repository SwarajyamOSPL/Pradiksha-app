<?php

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('it parses the site catalog markup and imports products with images and descriptions', function () {
    Storage::fake('public');

    // Use a unique slug per run: Windows file locks on a previous run's
    // fixture file (AV/indexer scans) can otherwise make this test flaky.
    // Derive it the same way the command does, so they always match.
    $name = 'Sample Gadget '.Str::random(8);
    $slug = Str::slug($name);

    // A 1x1 transparent PNG, so the WebP conversion has real image bytes to decode.
    $pngBytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

    $homepageHtml = <<<HTML
    <html><head></head><body>
    <script>
    var prdlist= '<ul class=\"sub_dp_dwn ps1 bg1 imgFnd\"><li><a href=\"/test-category.html#{$slug}\" >{$name}</a></li><li class=\"menu_btn\"><a href=\"/test-category.html\">View all &rsaquo;</a></li></ul>';var root_path='/home3/indiamart/x';
    </script>
    </body></html>
    HTML;

    $categoryHtml = <<<HTML
    <html><head></head><body>
    <a id="{$slug}" class="namePlace ps1"></a>
    <SCRIPT> var dataref1 = new Object(); dataref1 = eval([{"img_id":1,"img_path":"https://cdn.example.test/{$slug}-500x500.png","prd_name":"{$name}","prd_id":"123","isq_det_form":[{"FK_IM_SPEC_MASTER_DESC":"Form","SUPPLIER_RESPONSE_DETAIL":"Powder"},{"FK_IM_SPEC_MASTER_DESC":"Pack Size","SUPPLIER_RESPONSE_DETAIL":"100 g"},]}]);
    var isq_det_form=new Object(); isq_det_form =eval([]); var length = 0; var CAT_NAME = 'Test Category'; var t_prod_count = 1 ; var domain_alias = 'test';</SCRIPT>
    </body></html>
    HTML;

    // The 5.imimg.com logo URL is deliberately left unfaked, so Http::fake()'s
    // default stub (an empty 200) makes the logo conversion fail gracefully
    // without ever touching the real public/images/logo.webp on disk.
    Http::fake([
        'https://www.pradikshaherbal.co.in/' => Http::response($homepageHtml, 200),
        'https://www.pradikshaherbal.co.in/test-category.html' => Http::response($categoryHtml, 200),
        'https://cdn.example.test/*' => Http::response($pngBytes, 200, ['Content-Type' => 'image/png']),
    ]);

    $this->artisan('pradiksha:import-catalog')->assertExitCode(0);

    $product = Product::where('slug', $slug)->firstOrFail();

    expect($product->name)->toBe($name);
    expect($product->category)->toBe('Test Category');
    expect($product->description)->toBe('Form: Powder. Pack Size: 100 g.');
    expect($product->image_path)->toBe("products/{$slug}.webp");
    expect($product->source_url)->toBe('https://www.pradikshaherbal.co.in/test-category.html');

    Storage::disk('public')->assertExists("products/{$slug}.webp");
});

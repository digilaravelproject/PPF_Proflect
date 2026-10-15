<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PpfVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class PpfVariantController extends Controller {
 public function index(): View { return view('admin.ppf-variants.index',['variants'=>PpfVariant::withCount('claims')->latest()->paginate(20)]); }
 public function store(Request $request): RedirectResponse { PpfVariant::create($this->validated($request)); return back()->with('status','PPF variant added.'); }
 public function update(Request $request,PpfVariant $ppfVariant): RedirectResponse { $ppfVariant->update($this->validated($request,$ppfVariant)); return back()->with('status','PPF variant updated.'); }
 public function toggle(PpfVariant $ppfVariant): RedirectResponse { $ppfVariant->update(['is_active'=>!$ppfVariant->is_active]); return back()->with('status','PPF variant status updated.'); }
 public function destroy(PpfVariant $ppfVariant): RedirectResponse { $ppfVariant->delete(); return back()->with('status','PPF variant deleted.'); }
 private function validated(Request $request,?PpfVariant $variant=null): array { return $request->validate(['name'=>['required','string','max:120',Rule::unique('ppf_variants')->ignore($variant)],'description'=>['nullable','string','max:1000']]); }
}

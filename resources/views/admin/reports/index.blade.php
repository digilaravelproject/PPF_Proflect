<x-admin-layout title="Reports">
    <section class="form-page-heading"><span class="eyebrow">REPORTS & EXPORT</span><h2>Download operational data</h2><p>Select a dataset and optional date range, then export it as Excel or PDF.</p></section>
    <form class="panel report-form" method="GET" action="{{ route('admin.reports.export') }}">
        <div class="field"><label>Report type</label><div class="field__control"><select name="type" required><option value="all">All data</option><option value="customers">Customers</option><option value="plans">Plans</option><option value="subscriptions">Subscriptions</option><option value="claims">Claims</option><option value="payments">Payments</option></select></div></div>
        <div class="field"><label>From date <small>Optional</small></label><div class="field__control"><input type="date" name="from"></div></div>
        <div class="field"><label>To date <small>Optional</small></label><div class="field__control"><input type="date" name="to"></div></div>
        <div class="report-actions"><button class="button button--excel" name="format" value="excel">Export to Excel</button><button class="button button--pdf" name="format" value="pdf">Export to PDF</button></div>
    </form>
</x-admin-layout>

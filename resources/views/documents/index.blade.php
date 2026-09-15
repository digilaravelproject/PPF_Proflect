<x-customer-layout title="Documents">
    <section class="welcome-row"><div><h2>Your documents</h2><p>View and download warranty certificates for every protection plan.</p></div></section>
    <div class="document-grid">
        @forelse($subscriptions as $subscription)
            <article class="panel document-card"><span class="document-card__icon">PDF</span><div><span class="eyebrow">WARRANTY CERTIFICATE</span><h3>{{ $subscription->plan->name }}</h3><p>PF-{{ str_pad($subscription->id, 6, '0', STR_PAD_LEFT) }} · Valid until {{ $subscription->ends_at->format('d M Y') }}</p></div><a class="button button--dark" href="{{ route('documents.certificate', $subscription) }}">Download <span>⇩</span></a></article>
        @empty
            <section class="empty-state panel"><h3>No documents available</h3><p>Your warranty certificate will appear here after a plan is activated.</p><a class="button button--dark" href="{{ route('subscription.index') }}">View plans</a></section>
        @endforelse
    </div>
</x-customer-layout>

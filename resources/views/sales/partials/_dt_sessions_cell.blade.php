<div>
    <span class="text-muted">{{ trans('sales.remaining') ?? 'متبقي' }}:</span>
    <strong>{{ (int)$baseRemaining }}</strong>
    <span class="text-muted">/</span>
    <span>{{ (int)$baseIncluded }}</span>
</div>

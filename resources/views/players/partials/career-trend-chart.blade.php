<div class="w-full" x-show="{{ $chartVar }}" x-html="{{ $htmlVar }}"></div>
<p class="text-sm text-text-muted" x-show="!{{ $chartVar }}">{{ $emptyText }}</p>

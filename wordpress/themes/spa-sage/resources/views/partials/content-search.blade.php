<article @php(post_class('search-result py-4 first:pt-0'))>
  <div class="mb-1">
    <h2 class="h4 mb-0">
      <a href="{{ get_permalink() }}" class="text-secondary no-underline hover:text-primary transition-colors">
        {!! $title !!}
      </a>
    </h2>
  </div>

  @if (has_excerpt() || get_the_content())
    <div class="entry-summary max-w-3xl [&_p]:mb-0">
      @php(the_excerpt())
    </div>
  @endif
</article>

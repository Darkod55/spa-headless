<article @php(post_class('blog-post py-6 first:pt-0'))>
  <header class="mb-2">
    <h2 class="h4 mb-1">
      <a href="{{ get_permalink() }}" class="text-secondary no-underline hover:text-primary transition-colors">
        {!! $title !!}
      </a>
    </h2>

    <div class="entry-meta text-sm text-tertiary">
      @include('partials.entry-meta')
    </div>
  </header>

  <div class="entry-summary max-w-3xl text-tertiary [&_p]:mb-0">
    @php(the_excerpt())
  </div>
</article>

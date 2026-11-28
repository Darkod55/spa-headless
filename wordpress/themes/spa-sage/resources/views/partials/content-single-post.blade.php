@php
  $parsed = \App\helpers()->getContentWithToc();
  $content = $parsed['content'];
  $toc = $parsed['toc'];
  $categories = get_the_category();
  $relatedPosts = \App\helpers()->getRelatedPosts((int) get_the_ID());
  $gap = '20px';
  $relatedGridStyle = implode('; ', [
    '--grid-min-mobile: ' . \App\helpers()->resolveGridTrackMin('100%', $gap),
    '--grid-min-tablet: ' . \App\helpers()->resolveGridTrackMin('2', $gap),
    '--grid-min-desktop: ' . \App\helpers()->resolveGridTrackMin('3', $gap),
    '--grid-gap-col: ' . $gap,
    '--grid-gap-row: ' . $gap,
  ]);
@endphp

<article class="{{ implode(' ', get_post_class(['spa-single-post-article', 'h-entry'])) }}">
  <section class="p-clamp-xl bg-secondary text-white bs-block">
    <div class="container">
      <h1 class="p-name mb-4">{!! get_the_title() !!}</h1>
      <div class="spa-single-post__meta">
        <span class="spa-single-post__meta-item">
          <span class="spa-single-post__meta-icon" aria-hidden="true">
            {!! \App\helpers()->getSvg(['icon' => 'calendar', 'width' => 18, 'title' => '']) !!}
          </span>
          <time class="dt-published" datetime="{{ get_post_time('c', true) }}">
            {{ get_the_date('F j, Y') }}
          </time>
        </span>

        @if (! empty($categories))
          <span class="spa-single-post__meta-item">
            <span class="spa-single-post__meta-icon" aria-hidden="true">
              {!! \App\helpers()->getSvg(['icon' => 'tag', 'width' => 18, 'title' => '']) !!}
            </span>
            <span class="spa-single-post__meta-categories">
              @foreach ($categories as $index => $cat)
                @if ($index > 0)
                  <span aria-hidden="true">, </span>
                @endif
                <a href="{{ get_category_link($cat) }}" class="spa-single-post__meta-link">
                  {{ $cat->name }}
                </a>
              @endforeach
            </span>
          </span>
        @endif
      </div>
    </div>
  </section>

  <section class="p-clamp-xl">
    <div class="container">
      <div class="spa-single-post{{ empty($toc) ? ' is-full' : '' }}">
        <div class="spa-single-post__main">
          @if (has_post_thumbnail())
            <figure class="spa-single-post__featured">
              {!! get_the_post_thumbnail(null, 'large', [
                'class' => 'spa-single-post__featured-img',
              ]) !!}
            </figure>
          @endif

          <div class="e-content spa-single-post__content">
            {!! $content !!}
          </div>
        </div>

        @if (! empty($toc))
          <aside class="spa-single-post__aside" aria-label="{{ __('Table of contents', 'sage') }}">
            <nav class="spa-toc">
              <p class="spa-toc__label">{{ __('Table of Contents', 'sage') }}</p>
              <ul class="spa-toc__list">
                @foreach ($toc as $item)
                  <li class="spa-toc__item spa-toc__item--h{{ $item['level'] }}">
                    <a href="#{{ $item['id'] }}" class="spa-toc__link">
                      {{ $item['title'] }}
                    </a>
                  </li>
                @endforeach
              </ul>
            </nav>
          </aside>
        @endif
      </div>
    </div>
  </section>
</article>

@if (! empty($relatedPosts))
  <section class="p-clamp-xl spa-related-posts pt-0">
    <div class="container">
      <h2 class="spa-related-posts__title">{{ __('Related posts', 'sage') }}</h2>

      <div
        class="blog-posts-list spa-cards grid-auto-cards grid-auto-cards-gap"
        style="{!! esc_attr($relatedGridStyle) !!}"
      >
        @foreach ($relatedPosts as $relatedPost)
          @php
            $GLOBALS['post'] = $relatedPost;
            setup_postdata($relatedPost);
          @endphp
          @include('partials.content-post')
        @endforeach
        @php
          wp_reset_postdata();
        @endphp
      </div>
    </div>
  </section>
@endif

{!! \App\helpers()->renderBlockPattern('Logos with Text') !!}

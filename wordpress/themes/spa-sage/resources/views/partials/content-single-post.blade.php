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

<article class="{{ implode(' ', get_post_class(['mpx-single-post-article', 'h-entry'])) }}">
  <section class="p-clamp-xl bg-secondary text-white bs-block">
    <div class="container">
      <h1 class="p-name mb-4">{!! get_the_title() !!}</h1>
      <div class="mpx-single-post__meta">
        <span class="mpx-single-post__meta-item">
          <span class="mpx-single-post__meta-icon" aria-hidden="true">
            {!! \App\helpers()->getSvg(['icon' => 'calendar', 'width' => 18, 'title' => '']) !!}
          </span>
          <time class="dt-published" datetime="{{ get_post_time('c', true) }}">
            {{ get_the_date('F j, Y') }}
          </time>
        </span>

        @if (! empty($categories))
          <span class="mpx-single-post__meta-item">
            <span class="mpx-single-post__meta-icon" aria-hidden="true">
              {!! \App\helpers()->getSvg(['icon' => 'tag', 'width' => 18, 'title' => '']) !!}
            </span>
            <span class="mpx-single-post__meta-categories">
              @foreach ($categories as $index => $cat)
                @if ($index > 0)
                  <span aria-hidden="true">, </span>
                @endif
                <a href="{{ get_category_link($cat) }}" class="mpx-single-post__meta-link">
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
      <div class="mpx-single-post{{ empty($toc) ? ' is-full' : '' }}">
        <div class="mpx-single-post__main">
          @if (has_post_thumbnail())
            <figure class="mpx-single-post__featured">
              {!! get_the_post_thumbnail(null, 'large', [
                'class' => 'mpx-single-post__featured-img',
              ]) !!}
            </figure>
          @endif

          <div class="e-content mpx-single-post__content">
            {!! $content !!}
          </div>
        </div>

        @if (! empty($toc))
          <aside class="mpx-single-post__aside" aria-label="{{ __('Table of contents', 'sage') }}">
            <nav class="mpx-toc">
              <p class="mpx-toc__label">{{ __('Table of Contents', 'sage') }}</p>
              <ul class="mpx-toc__list">
                @foreach ($toc as $item)
                  <li class="mpx-toc__item mpx-toc__item--h{{ $item['level'] }}">
                    <a href="#{{ $item['id'] }}" class="mpx-toc__link">
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
  <section class="p-clamp-xl mpx-related-posts pt-0">
    <div class="container">
      <h2 class="mpx-related-posts__title">{{ __('Related posts', 'sage') }}</h2>

      <div
        class="blog-posts-list mpx-cards grid-auto-cards grid-auto-cards-gap"
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

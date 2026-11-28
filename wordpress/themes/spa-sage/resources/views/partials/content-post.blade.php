@php
  $title = get_the_title();
  $permalink = get_permalink();
  $hasThumb = has_post_thumbnail();
@endphp

<article class="{{ implode(' ', get_post_class(['spa-card', 'spa-card--image', 'is-bleed-image', 'spa-post-card'])) }}">
  <figure class="wp-block-image spa-post-card__media">
    @if ($hasThumb)
      <a href="{{ $permalink }}" tabindex="-1" aria-hidden="true">
        {!! get_the_post_thumbnail(null, 'large', [
          'class' => 'spa-post-card__image',
          'alt' => $title,
          'loading' => 'lazy',
        ]) !!}
      </a>
    @else
      <a
        href="{{ $permalink }}"
        class="spa-post-card__placeholder"
        tabindex="-1"
        aria-hidden="true"
      ></a>
    @endif
  </figure>

  <h3 class="h4 spa-post-card__title">
    <a href="{{ $permalink }}">{!! $title !!}</a>
  </h3>

  <div class="spa-post-card__footer">
    <time class="spa-post-card__date mb-4 font-bold" datetime="{{ get_the_date(DATE_W3C) }}">
      {{ get_the_date() }}
    </time>
    <a href="{{ $permalink }}" class="btn btn-white btn-sm">
      {{ __('Read more', 'sage') }}
      <span class="sr-only">{{ sprintf(__('about %s', 'sage'), $title) }}</span>
    </a>
  </div>
</article>

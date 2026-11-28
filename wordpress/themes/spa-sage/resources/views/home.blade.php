@extends('layouts.app-wide')

@php
  $archive = \App\helpers()->mpxGetArchivePageContent('post');
  $gap = '20px';
  $gridStyle = implode('; ', [
    '--grid-min-mobile: ' . \App\helpers()->resolveGridTrackMin('100%', $gap),
    '--grid-min-tablet: ' . \App\helpers()->resolveGridTrackMin('2', $gap),
    '--grid-min-desktop: ' . \App\helpers()->resolveGridTrackMin('3', $gap),
    '--grid-gap-col: ' . $gap,
    '--grid-gap-row: ' . $gap,
  ]);
@endphp

@section('content')
  @if ($archive['hero_banner_block'])
    {!! $archive['hero_banner_block'] !!}
  @else
    <section class="p-clamp-xl bg-secondary text-white bs-block">
      <div class="container">
        <h1 class="mb-0">{!! $page_title !!}</h1>
      </div>
    </section>
  @endif

  <section class="p-clamp-xl">
    <div class="container">
      @if (! have_posts())
        <p class="mb-8 max-w-xl">
          {{ __('No posts have been published yet.', 'sage') }}
        </p>
      @else
        <div
          class="blog-posts-list spa-cards grid-auto-cards grid-auto-cards-gap"
          style="{!! esc_attr($gridStyle) !!}"
        >
          @while (have_posts())
            @php
              the_post();
            @endphp
            @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
          @endwhile
        </div>

        @include('partials.pagination')
      @endif
    </div>
  </section>

  {!! $archive['remaining_content'] !!}
@endsection

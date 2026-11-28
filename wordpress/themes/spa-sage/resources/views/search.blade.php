@extends('layouts.app-wide')

@php
  $mainQuery = $GLOBALS['wp_query'];
  $query = get_search_query();
  $hasQuery = $query !== '';
  $hasResults = $mainQuery->post_count > 0;
  // Prefer found_posts; fall back to post_count if no_found_rows was on.
  $foundPosts = (int) $mainQuery->found_posts;
  if ($foundPosts < 1 && $hasResults) {
      $foundPosts = (int) $mainQuery->post_count;
  }
@endphp

@section('content')
  <section class="p-clamp-xl bg-secondary text-white bs-block">
    <div class="container">
      <div class="spa-subheading text-white mb-4">
        <p>{{ __('Search', 'sage') }}</p>
      </div>
      <h1 class="mb-0">
        @if ($hasQuery)
          {{ sprintf(__('Results for “%s”', 'sage'), $query) }}
        @else
          {{ __('Search results', 'sage') }}
        @endif
      </h1>
    </div>
  </section>

  <section class="p-clamp-xl">
    <div class="container">
      @if (! $hasResults)
        <p class="mb-8 max-w-xl">
          @if ($hasQuery)
            {{ __('No results matched your search. The page may have moved, or try heading back to the homepage.', 'sage') }}
          @else
            {{ __('Enter a search term to find pages on this site, or return to the homepage.', 'sage') }}
          @endif
        </p>
        <a href="{{ home_url('/') }}" class="btn btn-primary">
          {{ __('Back to home', 'sage') }}
        </a>
      @else
        <p class="mb-4 text-tertiary">
          {{ sprintf(
            _n('%s result found', '%s results found', $foundPosts, 'sage'),
            number_format_i18n($foundPosts)
          ) }}
        </p>

        <div class="search-results-list divide-y divide-tertiary">
          @while (have_posts())
            @php(the_post())
            @include('partials.content-search')
          @endwhile
        </div>

        @php($navigation = get_the_posts_navigation())
        @if ($navigation)
          <div class="mt-10 pt-8 border-t border-tertiary">
            {!! $navigation !!}
          </div>
        @endif
      @endif
    </div>
  </section>
@endsection

@extends('layouts.app-wide')

@section('content')
  <section class="p-clamp-xl bg-secondary text-white bs-block">
    <div class="container">
      <div class="mpx-subheading text-white mb-4">
        <p>{{ __('404', 'sage') }}</p>
      </div>
      <h1 class="mb-6">{{ __('Page not found', 'sage') }}</h1>
      {!! get_search_form(false) !!}
    </div>
  </section>

  <section class="p-clamp-xl">
    <div class="container">
      <p class="mb-8 max-w-xl">
        {{ __('The page you are looking for may have been moved, renamed, or is no longer available.', 'sage') }}
      </p>
      <a href="{{ home_url('/') }}" class="btn btn-primary">
        {{ __('Back to home', 'sage') }}
      </a>
    </div>
  </section>
@endsection

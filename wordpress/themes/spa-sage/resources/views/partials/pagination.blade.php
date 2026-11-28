@php
  $prev_icon = \App\helpers()->getSvg(['icon' => 'chevron-left', 'width' => 28, 'title' => '']);
  $next_icon = \App\helpers()->getSvg(['icon' => 'chevron-right', 'width' => 28, 'title' => '']);

  $pagination = get_the_posts_pagination([
    'mid_size' => 1,
    'end_size' => 1,
    'class' => 'spa-pagination',
    'aria_label' => __('Posts', 'sage'),
    'prev_text' => '<span class="spa-pagination__arrow" aria-hidden="true">' . $prev_icon . '</span><span class="sr-only">' . esc_html__('Previous page', 'sage') . '</span>',
    'next_text' => '<span class="spa-pagination__arrow" aria-hidden="true">' . $next_icon . '</span><span class="sr-only">' . esc_html__('Next page', 'sage') . '</span>',
  ]);

  // Drop the visible/screen-reader heading WP injects into the nav.
  $pagination = preg_replace('/<h2[^>]*>.*?<\/h2>/is', '', (string) $pagination);
@endphp

@if ($pagination)
  {!! $pagination !!}
@endif

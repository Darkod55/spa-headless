@include('components.header-icons')

@if (has_nav_menu('preheader_navigation'))
  <div class="preheader hidden lg:block bg-primary px-clamp text-white">
    <div class="container flex items-center justify-end py-3">
      <nav class="preheader-nav" aria-label="{{ wp_get_nav_menu_name('preheader_navigation') }}">
        {!! wp_nav_menu([
          'theme_location' => 'preheader_navigation',
          'menu_class' => 'flex items-center gap-5',
          'container' => false,
          'depth' => 1,
          'echo' => false,
        ]) !!}
      </nav>
    </div>
  </div>
@endif

<header id="main-header" class="banner header-bar sticky z-30 bg-tertiary px-clamp transition-all duration-300" x-data="{ mobileOpen: false }" x-effect="document.body.classList.toggle('overflow-hidden', mobileOpen)">
  <div class="container flex items-center justify-between gap-6 py-5 lg:py-6">
    <div class="brand shrink-0">
      {!! \App\helpers()->getSiteLogo([
        'link_classes' => 'inline-flex items-center',
        'custom_logo_class' => 'site-logo transition-all duration-300',
      ]) !!}
    </div>

    @if (has_nav_menu('primary_navigation'))
      <nav class="primary-nav hidden items-center lg:flex" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
        {!! wp_nav_menu([
          'theme_location' => 'primary_navigation',
          'menu_class' => 'flex items-center gap-8 xl:gap-6',
          'container' => false,
          'echo' => false,
        ]) !!}
      </nav>
    @endif

    {{--
      Off-canvas menu toggle - a single button that swaps its icon based
      on `mobileOpen` rather than two separate open/close buttons, so
      the click target is always the exact same element/position
      whether the menu is opening or closing. Needs a higher z-index
      than the off-canvas backdrop/panel below (z-40/z-50) so it stays
      on top of them (and clickable) once the panel is open - <header>
      itself only goes up to z-30, precisely so it stays underneath.
    --}}
    <button
      type="button"
      class="relative z-[60] text-primary lg:hidden"
      @click="mobileOpen = ! mobileOpen"
      :aria-expanded="mobileOpen"
      :aria-label="mobileOpen ? '{{ __('Close menu', 'sage') }}' : '{{ __('Open menu', 'sage') }}'"
    >
      <span x-show="! mobileOpen">@svgIcon('menu'|25|25)</span>
      <span class="text-white" x-show="mobileOpen" x-cloak>@svgIcon('close'|25|25)</span>
    </button>
  </div>

  {{-- Off-canvas backdrop --}}
  <div
    x-show="mobileOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-40 bg-black/50 lg:hidden"
    @click="mobileOpen = false"
  ></div>

  {{-- Off-canvas menu panel, slides in from the right, covering the full viewport --}}
  @if (has_nav_menu('primary_navigation'))
    <div
      x-show="mobileOpen"
      x-cloak
      x-transition:enter="duration-300 ease-out"
      x-transition:enter-start="translate-x-full"
      x-transition:enter-end="translate-x-0"
      x-transition:leave="duration-200 ease-in"
      x-transition:leave-start="translate-x-0"
      x-transition:leave-end="translate-x-full"
      class="offcanvas-nav fixed inset-0 z-50 flex flex-col overflow-y-auto bg-navy-light px-clamp py-5 text-white lg:hidden"
    >
      {{--
        Logo + spacer row: matches the main bar's py-5 / toggle height so the
        header close button (z-60) stays aligned top-right over the spacer.
      --}}
      <div class="flex shrink-0 items-center justify-between gap-6">
        <div class="brand shrink-0">
          {!! \App\helpers()->getSiteLogo([
            'link_classes' => 'inline-flex items-center text-white',
            'custom_logo_class' => 'site-logo transition-all duration-300',
          ]) !!}
        </div>
        <div class="size-7 shrink-0" aria-hidden="true"></div>
      </div>

      @if (has_nav_menu('primary_navigation'))
        <nav class="mt-8" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
          {!! wp_nav_menu([
            'theme_location' => 'primary_navigation',
            'menu_class' => 'flex flex-col divide-y divide-white/10 uppercase tracking-[1px] text-[14px]',
            'offcanvas' => true,
            'echo' => false,
          ]) !!}
        </nav>
      @endif
    </div>
  @endif
</header>

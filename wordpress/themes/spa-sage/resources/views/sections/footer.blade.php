<footer class="site-footer border-t border-white/20 bg-dark-blue text-white">
  <div class="px-clamp py-clamp-xl">
    <div class="container grid grid-cols-1 items-start gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,1fr)] lg:gap-10 xl:gap-14">
      {{-- Newsletter --}}
      <div class="footer-signup mx-auto w-full max-w-sm lg:mx-0">
        <div id="fd-form-6a888c4b092d2b69f165dfc1"></div>
        <script>
          window.fd('form', {
            formId: '6a888c4b092d2b69f165dfc1',
            containerEl: '#fd-form-6a888c4b092d2b69f165dfc1'
          });
        </script>
      </div>

      {{-- Logo + primary links --}}
      <div class="flex flex-col items-center gap-8 text-center">
        <div class="brand">
          {!! \App\helpers()->getSiteLogo([
            'link_classes' => 'inline-flex items-center justify-center text-white',
            'custom_logo_class' => 'site-logo transition-all duration-300',
            'logo_variant' => 'alt',
          ]) !!}
        </div>

        @if (has_nav_menu('footer_navigation'))
          <nav class="footer-menu-nav" aria-label="{{ wp_get_nav_menu_name('footer_navigation') }}">
            {!! wp_nav_menu([
              'theme_location' => 'footer_navigation',
              'menu_class' => 'footer-menu flex flex-wrap items-center justify-center gap-x-5 gap-y-3',
              'container' => false,
              'depth' => 1,
              'echo' => false,
            ]) !!}
          </nav>
        @endif
      </div>

      {{-- Social --}}
      @if (has_nav_menu('social_navigation'))
        <div class="flex flex-col items-center gap-4 lg:items-end">
          <nav class="footer-nav" aria-label="{{ wp_get_nav_menu_name('social_navigation') }}">
            <h3 class="footer-nav__title text-center lg:text-right">{{ __('Follow Edy', 'sage') }}</h3>
            {!! wp_nav_menu([
              'theme_location' => 'social_navigation',
              'menu_class' => 'footer-social flex flex-wrap items-center justify-center gap-2.5 lg:justify-end',
              'container' => false,
              'depth' => 1,
              'echo' => false,
            ]) !!}
          </nav>
        </div>
      @else
        <div class="hidden lg:block" aria-hidden="true"></div>
      @endif
    </div>
  </div>

  <div class="footer-bar border-t border-white/20 px-clamp py-6 text-center text-sm text-white/55">
    <p class="m-0">
      {{ sprintf(
        __('© %2$s Edy Nathan. All Rights Reserved.', 'sage'),
        '2025',
        date('Y')
      ) }}
    </p>
  </div>
</footer>

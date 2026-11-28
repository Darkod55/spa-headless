<form role="search" method="get" class="search-form flex flex-col gap-3 sm:flex-row sm:items-stretch max-w-xl" action="{{ home_url('/') }}">
  <label class="m-0 min-w-0 flex-1">
    <span class="sr-only">
      {{ _x('Search for:', 'label', 'sage') }}
    </span>

    <input
      type="search"
      class="box-border w-full rounded-[5px] border border-tertiary bg-white px-4 py-2.5 text-[17px] text-secondary outline-none focus:border-primary"
      placeholder="{!! esc_attr_x('Search…', 'placeholder', 'sage') !!}"
      value="{!! get_search_query() !!}"
      name="s"
    >
  </label>

  <button type="submit" class="btn btn-primary shrink-0">
    {{ _x('Search', 'submit button', 'sage') }}
  </button>
</form>

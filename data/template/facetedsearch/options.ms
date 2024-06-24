<div class="result-pagination">« {{previous}} | <span class="page_entries"><strong>{{offset}}</strong> - <strong>{{limit}}</strong> of <strong>{{count}}</strong></span> | {{next}} »</div>
<div class="options-fields" >
  <span class="options-field">
    <select name="format" form="search-input-form" id="format" class="smw-factedsearch-format-field select-field" title="{{format-title}}" onchange="this.form.submit()">{{format-options}}</select>
  </span>
  <span class="options-field">
    <select name="order" form="search-input-form" id="order" class="smw-factedsearch-sort-field select-field" title="{{order-title}}" onchange="this.form.submit()">{{order-options}}</select>
  </span>
  <span class="options-field">
    <select name="size" form="search-input-form" id="size" class="smw-factedsearch-size-field select-field" title="{{size-title}}" onchange="this.form.submit()">{{size-options}}</select>
  </span>
</div>
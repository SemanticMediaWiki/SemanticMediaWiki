<div class="smw-factedsearch-search-form">
  <form id="search-input-form" class="search-query-form clearfix  catalog-search" action="{{action}}" accept-charset="UTF-8" method="{{method}}">
    <div class="input-group">

      <div class="input-group-select">
      <select name="profile" id="profile_field" form="search-input-form" class="smw-factedsearch-profile" title="{{profile-title}}" onchange="this.form.submit()" {{profile-select-disabled}}>{{profile-options}}</select>
      </div>
      <div class="input-group-input">
      <input type="search" name="q" id="q" value="{{q}}" class="smw-factedsearch-search-input search-input" placeholder="{{search-placeholder-label}}" autocomplete="off" autofocus="autofocus">

      <button type="submit" class="search-button" id="search">
        <span class="submit-search-text">{{search-label}}</span>
        <span class="searchicon"></span>
      </button>

      <button type="submit" class="reset-button" id="reset" name="reset" value="true" title="Reset filters">
        <span class="reseticon"></span>
       </button>
      </div>
    </div>
    <input name="utf8" type="hidden" value="✓">
    <input name="limit" type="hidden" value="{{limit}}">
    <input name="offset" type="hidden" value="{{offset}}">
    <input name="csum" type="hidden" value="{{csum}}">
    {{hidden}}
  </form>
</div>
@extends('layouts.public')
@section('content')
@php
 $arrival=\Carbon\Carbon::parse($search['check_in']);
 $departure=\Carbon\Carbon::parse($search['check_out']);
 $stayQuery=['check_in'=>$search['check_in'],'check_out'=>$search['check_out'],'guests'=>(int)ceil(($search['adults']+$search['children'])/$search['rooms']),'adults'=>$search['adults'],'children'=>$search['children'],'rooms'=>$search['rooms']];
@endphp
<section class="marketplace-search-section" aria-label="Find a hotel">
 <form method="get" action="/#collection" class="marketplace-search" data-stay-search>
  <div class="stay-search-field destination-field">
   <label for="city">City, destination, or hotel name</label>
   <div class="stay-search-control"><x-icon name="pin"/><input id="city" name="city" value="{{ old('city',$search['city']) }}" placeholder="City, hotel, place to go" maxlength="80" autocomplete="off" list="destinations"></div>
   <datalist id="destinations">@foreach(['Bali','Bandung','Yogyakarta','Lombok','Jakarta','Malang','Bogor','Surabaya'] as $destination)<option value="{{ $destination }}">@endforeach</datalist>
   @error('city')<span class="search-error">{{ $message }}</span>@enderror
  </div>
  <div class="stay-search-field dates-field">
   <span id="dates-label" class="stay-search-label">Check-in &amp; Check-out Dates</span>
   <details class="stay-search-details" data-search-panel>
    <summary class="stay-search-control" aria-labelledby="dates-label dates-summary"><x-icon name="calendar"/><span id="dates-summary" data-dates-summary>{{ $arrival->format('D, d M') }} – {{ $departure->format('D, d M') }} ({{ count($nights) }} {{ count($nights)===1?'night':'nights' }})</span></summary>
    <div class="stay-search-popover dates-popover">
     <h3>When are you staying?</h3>
     <div class="stay-date-inputs">
      <label for="search-check-in">Check-in<input id="search-check-in" type="date" name="check_in" value="{{ old('check_in',$search['check_in']) }}" min="{{ today()->toDateString() }}" required></label>
      <label for="search-check-out">Check-out<input id="search-check-out" type="date" name="check_out" value="{{ old('check_out',$search['check_out']) }}" min="{{ $arrival->copy()->addDay()->toDateString() }}" required></label>
     </div>
     <p class="field-help">Choose a stay of 1 to 90 nights.</p>
     <button class="button primary" type="button" data-search-done>Done</button>
    </div>
   </details>
   @error('check_in')<span class="search-error">{{ $message }}</span>@enderror
   @error('check_out')<span class="search-error">{{ $message }}</span>@enderror
  </div>
  <div class="stay-search-field guests-field">
   <span id="guests-label" class="stay-search-label">Guests and Rooms</span>
   <details class="stay-search-details" data-search-panel>
    <summary class="stay-search-control" aria-labelledby="guests-label guests-summary"><x-icon name="users"/><span id="guests-summary" data-guests-summary>{{ $search['adults'] }} {{ $search['adults']===1?'adult':'adults' }}, {{ $search['children'] }} {{ $search['children']===1?'child':'children' }}, {{ $search['rooms'] }} {{ $search['rooms']===1?'room':'rooms' }}</span></summary>
    <div class="stay-search-popover guests-popover">
     <h3>Who’s coming along?</h3>
     @foreach([['adults','Adults',1,24,$search['adults']],['children','Children',0,12,$search['children']],['rooms','Rooms',1,8,$search['rooms']]] as [$name,$label,$min,$max,$value])
     <div class="stay-guest-row"><label for="search-{{ $name }}">{{ $label }}</label><div class="stay-stepper"><button type="button" data-step="-1" data-for="search-{{ $name }}" aria-label="Remove one {{ $name==='children'?'child':($name==='adults'?'adult':'room') }}">−</button><input type="number" id="search-{{ $name }}" name="{{ $name }}" min="{{ $min }}" max="{{ $max }}" value="{{ old($name,$value) }}" required><button type="button" data-step="1" data-for="search-{{ $name }}" aria-label="Add one {{ $name==='children'?'child':($name==='adults'?'adult':'room') }}">+</button></div></div>
     @endforeach
     <p class="field-help">At least one adult per room. Children count toward room capacity.</p>
     <button class="button primary" type="button" data-search-done>Done</button>
    </div>
   </details>
   @foreach(['adults','children','rooms'] as $field)@error($field)<span class="search-error">{{ $message }}</span>@enderror @endforeach
  </div>
  <button class="stay-search-submit" type="submit" aria-label="Search hotels"><x-icon name="search"/><span>Search hotels</span></button>
 </form>
</section>
<section class="public-hero"><div><div class="eyebrow">INDEPENDENT SPIRIT. EXCEPTIONAL STAYS.</div><h1>Some places<br>just feel different.</h1><p>Discover thoughtful hideaways and independent hotels. Book directly with the people who make your stay special.</p><a href="#collection" class="button primary">Find your kind of place<x-icon name="arrow"/></a><div class="amenities"><span>Direct booking</span><span>Local hospitality</span><span>Thoughtfully selected</span></div></div><img src="/assets/images/ubud-retreat.jpg" class="hero-art" alt="AI-generated tropical hotel courtyard with a swimming pool and arched terrace" fetchpriority="high" width="1200" height="1017"></section>
<section class="public-section" id="collection">
 <div class="public-section-heading"><div><div class="eyebrow">THE COLLECTION</div><h2>{{ request()->hasAny(['city','check_in','adults','rooms'])?'Find your next stay.':'Stay somewhere meaningful.' }}</h2><p>{{ $properties->count() }} {{ $properties->count()===1?'property':'properties' }} available{{ $search['city']?' for “'.$search['city'].'”':'' }} · {{ count($nights) }} {{ count($nights)===1?'night':'nights' }} · {{ $search['adults']+$search['children'] }} guests · {{ $search['rooms'] }} {{ $search['rooms']===1?'room':'rooms' }}</p></div>@if(request()->query())<a href="/#collection" class="text-link">Reset search</a>@endif</div>
 @if($search['rooms']>1)<p class="marketplace-booking-note">Hotels below have capacity for your group across {{ $search['rooms'] }} rooms. Reserve each room separately with the property.</p>@endif
 <div class="property-grid">
 @forelse($properties as $hotel)
 @php $photo=config('marketplace.properties.'.$hotel->slug,config('marketplace.fallback'));$hotelUrl=url('/hotels/'.$hotel->slug).'?'.http_build_query($stayQuery); @endphp
 <article class="property-card"><a href="{{ $hotelUrl }}"><img src="{{ $photo['src'] }}" alt="{{ $photo['alt'] }}" loading="lazy" decoding="async" width="1200" height="750"></a><div class="property-card-content"><small><x-icon name="pin" style="width:12px;height:12px"/> {{ $hotel->city }}</small><a href="{{ $hotelUrl }}"><h3>{{ $hotel->name }}</h3></a><p>{{ $hotel->tagline }}</p><small>{{ $hotel->available_rooms_count }} rooms available for your dates</small><div class="property-price"><span><small>From</small> <strong>Rp {{ number_format($hotel->available_min_rate,0,',','.') }}</strong><small> / room / night before tax</small></span><a class="icon-button" href="{{ $hotelUrl }}" aria-label="Explore {{ $hotel->name }}"><x-icon name="arrow"/></a></div></div></article>
 @empty<div class="empty marketplace-empty"><x-icon name="search"/><h3>No stays match your search.</h3><p>Try a different destination, date range, or number of guests and rooms.</p><a class="button" href="/#collection">Reset search</a></div>@endforelse
 </div>
 <p class="marketplace-demo-note">Demo collection · All hotels and guest records shown here are fictional.</p>
</section>
@endsection

@extends('layouts.admin')
@section('title','Overview')
@section('content')
@php
 $operational=$rooms->where('out_of_service',false);
 $clean=$operational->where('housekeeping_status','clean')->count();
 $pending=$operational->where('housekeeping_status','dirty')->count();
 $inspection=$operational->where('housekeeping_status','inspecting')->count();
 $progress=$operational->count()?round($clean/$operational->count()*100):0;
 $arrivalMax=max(5,$dashboard['arrivalSeries']->max(fn($d)=>max($d['current'],$d['previous'])));
 $sourceMax=max(1,$dashboard['sourceSeries']->max(fn($d)=>$d['counts']->max()??0));
 $sourceLabels=['website'=>'Hotel website','frontdesk'=>'Front desk','marketplace'=>'Marketplace'];
@endphp
<div class="overview-grid">
 <div class="arrival-overview">
  <div class="compact-kpis">
   <section class="panel compact-kpi"><span class="metric-icon"><x-icon name="bed"/></span><h2>Available rooms</h2><strong class="metric-value">{{ max(0,$operational->count()-$stats['inHouse']) }}</strong><small>Not currently occupied</small></section>
   <section class="panel compact-kpi"><span class="metric-icon"><x-icon name="logout"/></span><h2>Check out</h2><strong class="metric-value">{{ $departures->count() }}</strong><small>Due today or overdue</small></section>
  </div>
  <section class="panel checkin-panel">
   <div class="checkin-top"><div><h2>Check in</h2><strong class="metric-value">{{ $dashboard['arrivalSeries']->sum('current') }}</strong></div><div class="segmented" aria-label="Arrival chart period"><a href="/app?period=month" @if($dashboard['period']==='month') aria-current="true" @endif>Month</a><a href="/app?period=year" @if($dashboard['period']==='year') aria-current="true" @endif>Year</a></div></div>
   <p class="chart-description">Scheduled arrivals · {{ $dashboard['period']==='year'?today()->year:'last 4 months' }}</p>
   <div class="comparison-chart"><div class="chart-scale">@foreach([1,.75,.5,.25,0] as $fraction)<span>{{ round($arrivalMax*$fraction) }}</span>@endforeach</div><div class="comparison-bars">
    @foreach($dashboard['arrivalSeries'] as $d)<div class="comparison-column"><div class="bar-pair" tabindex="0" aria-label="{{ $d['date'] }}: {{ $d['current'] }} scheduled arrivals, {{ $d['previous'] }} in the same period last year"><div class="previous-bar" style="height:{{ $d['previous']/$arrivalMax*100 }}%"></div><div class="current-bar" style="height:{{ $d['current']/$arrivalMax*100 }}%"></div><div class="chart-tooltip"><strong>{{ $d['date'] }}</strong><span>This year <b>{{ $d['current'] }}</b></span><span>Last year <b>{{ $d['previous'] }}</b></span></div></div><span>{{ $d['label'] }}</span></div>@endforeach
   </div></div>
   <div class="mini-legend"><span><i class="legend-mark purple"></i>This year</span><span><i class="legend-mark lavender"></i>Last year</span></div>
  </section>
 </div>
 <section class="panel source-panel"><div class="panel-header"><div><h2>Booking by source</h2><p>Reservations created in the last 14 days</p></div><a class="icon-button" href="/app/reports" aria-label="View booking reports"><x-icon name="chart"/></a></div>
  @if($dashboard['sources']->isNotEmpty())<div class="source-chart" role="img" aria-label="{{ $dashboard['sources']->map(fn($s)=>($sourceLabels[$s]??ucfirst($s)).': '.$dashboard['sourceSeries']->sum(fn($d)=>$d['counts'][$s]))->join(', ') }} reservations in the last 14 days">
   @foreach($dashboard['sourceSeries'] as $d)<div class="source-day" tabindex="0" aria-label="{{ $d['date']->format('d M') }}: {{ $d['counts']->map(fn($count,$s)=>($sourceLabels[$s]??ucfirst($s)).' '.$count)->join(', ') }}"><div class="source-stem"></div>@foreach($d['counts'] as $source=>$count)<i class="source-dot series-{{ $loop->index%3 }}" style="bottom:{{ 10+$count/$sourceMax*80 }}%"></i>@endforeach<div class="chart-tooltip"><strong>{{ $d['date']->format('d M') }}</strong>@foreach($d['counts'] as $source=>$count)<span>{{ $sourceLabels[$source]??ucfirst($source) }} <b>{{ $count }}</b></span>@endforeach</div></div>@endforeach
  </div><div class="source-legend">@foreach($dashboard['sources'] as $source)<span><i class="legend-mark series-{{ $loop->index%3 }}"></i>{{ $sourceLabels[$source]??ucfirst($source) }}</span>@endforeach</div>
  @else<div class="empty">No reservations created in the last 14 days. Create a reservation to see its source here.</div>@endif
 </section>
 <section class="panel readiness-panel"><div class="panel-header"><div><h2>Housekeeping</h2><p>Room readiness across your property</p></div><a class="button small" href="/app/housekeeping">All rooms<x-icon name="down"/></a></div>
  <div class="readiness-summary"><div><span>Total<x-icon name="bed"/></span><strong>{{ $operational->count() }}<small>rooms</small></strong></div><div><span>Cleaned<x-icon name="clean"/></span><strong>{{ $clean }}<small>rooms</small></strong></div><div><span>Pending<x-icon name="clock"/></span><strong>{{ $pending }}<small>rooms</small></strong></div><div><span>Progress</span><strong>{{ $progress }}<small>%</small></strong><div class="readiness-track"><i style="width:{{ $progress }}%"></i></div></div></div>
  <div class="readiness-rows">@forelse($rooms->groupBy('floor')->take(3) as $floor=>$floorRooms)<a href="/app/housekeeping" class="readiness-row"><span class="floor-icon"><x-icon name="building"/></span><span class="floor-name"><strong>{{ $floor?:'Unassigned floor' }}</strong><small>{{ $floorRooms->count() }} rooms</small></span><span><strong>{{ $floorRooms->where('housekeeping_status','clean')->count() }}</strong><small>Clean</small></span><span><strong>{{ $floorRooms->where('housekeeping_status','dirty')->count() }}</strong><small>To clean</small></span><span><strong>{{ $floorRooms->where('housekeeping_status','inspecting')->count() }}</strong><small>Inspecting</small></span></a>@empty<div class="empty">No rooms yet. Add rooms in Rooms & rates to manage readiness.</div>@endforelse</div>
  <div class="readiness-note">{{ $inspection }} awaiting inspection · {{ $rooms->where('out_of_service',true)->count() }} out of service</div>
 </section>
 <section class="panel room-status-panel"><div class="panel-header"><div><h2>Room status</h2><p>Current occupancy and room readiness</p></div><a class="icon-button" href="/app/rooms" aria-label="View all rooms and rates"><x-icon name="bed"/></a></div>
  <div class="room-mini-grid">@forelse($rooms->take(18) as $room)@php $occupied=in_array($room->id,$occupiedRoomIds,true);$booked=$calendar->contains(fn($r)=>$r->room_id===$room->id&&$r->status==='confirmed'&&$r->check_in->lte(today()));$state=$room->out_of_service?'out':($occupied?'occupied':($booked?'booked':($room->housekeeping_status!=='clean'?'dirty':'ready')));$stateLabel=['out'=>'Out of service','occupied'=>'In house','booked'=>'Booked tonight','dirty'=>ucfirst($room->housekeeping_status),'ready'=>'Clean'][$state]; @endphp<a href="/app/rooms" title="Room {{ $room->number }} · {{ $stateLabel }}" aria-label="Room {{ $room->number }}, {{ $stateLabel }}" class="room-mini {{ $state }}">{{ $room->number }}</a>@empty<div class="empty">No rooms yet. Add a room to see its status.</div>@endforelse</div>
  <div class="room-legend"><span><i class="legend-mark occupied"></i>In house</span><span><i class="legend-mark dirty"></i>Need cleaning</span><span><i class="legend-mark booked"></i>Booked</span></div>@if($rooms->count()>18)<a class="all-rooms-link" href="/app/rooms">View all {{ $rooms->count() }} rooms</a>@endif
 </section>
 <div class="week-profile-row">
  <section class="panel week-panel">
   @php $capacity=max(1,$operational->count());$points=fn($scale)=>collect(range(0,6))->map(fn($i)=>round(90+sin($i*2*pi()/7)*65*$scale,2).','.round(84-cos($i*2*pi()/7)*65*$scale,2))->join(' ');$bookedPoints=$week->values()->map(fn($d,$i)=>round(90+sin($i*2*pi()/7)*65*min(1,$d['count']/$capacity),2).','.round(84-cos($i*2*pi()/7)*65*min(1,$d['count']/$capacity),2))->join(' '); @endphp
   <svg class="week-radar" viewBox="0 0 180 178" role="img" aria-label="Booked rooms in the next seven days: {{ $week->map(fn($d)=>$d['date']->format('D d').': '.$d['count'])->join(', ') }}">@foreach([.25,.5,.75,1] as $scale)<polygon points="{{ $points($scale) }}" class="radar-grid"/>@endforeach @foreach($week->values() as $i=>$d)@php $x=90+sin($i*2*pi()/7)*65;$y=84-cos($i*2*pi()/7)*65; @endphp<line x1="90" y1="84" x2="{{ $x }}" y2="{{ $y }}" class="radar-grid"/><text x="{{ 90+sin($i*2*pi()/7)*80 }}" y="{{ 87-cos($i*2*pi()/7)*80 }}" text-anchor="middle">{{ $d['date']->format('D') }}</text>@endforeach<polygon points="{{ $bookedPoints }}" class="radar-area"/></svg>
   <div class="week-summary"><h2>Week ahead</h2><strong>{{ $week->sum('count') }}</strong><small>booked room nights</small><span class="occupancy-label">{{ $stats['occupancy'] }}% in house now</span><a class="text-link" href="/app/calendar">View availability</a></div>
  </section>
  <section class="panel profile-panel"><div class="profile-top"><x-icon name="users"/><span>Your workspace</span></div><h2>{{ auth()->user()->name }}</h2><p>{{ ucfirst(auth()->user()->role) }}</p><span class="profile-initial">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><a href="/app/mobile" class="icon-button" aria-label="Open mobile workspace"><x-icon name="arrow"/></a></section>
 </div>
 <section class="panel booking-list-panel"><div class="panel-header"><div><h2>Booking list</h2><p>Today's arrivals and departures</p></div><button class="icon-button" data-open="new-reservation" aria-label="New reservation"><x-icon name="plus"/></button></div>
  <div class="panel-tabs" role="tablist" aria-label="Today's stays"><button id="arrivals-tab" role="tab" aria-controls="arrivals-panel" aria-selected="true" class="active" data-tab="arrivals">Arrivals <span>{{ $arrivals->count() }}</span></button><button id="departures-tab" role="tab" aria-controls="departures-panel" aria-selected="false" tabindex="-1" data-tab="departures">Departures <span>{{ $departures->count() }}</span></button><a class="text-link" href="/app/reservations">All bookings</a></div>
  @foreach(['arrivals'=>$arrivals,'departures'=>$departures] as $tab=>$stays)<div id="{{ $tab }}-panel" role="tabpanel" aria-labelledby="{{ $tab }}-tab" data-tab-panel="{{ $tab }}" @if($tab!=='arrivals') hidden @endif><div class="table-scroll"><table><thead><tr><th>Guest name</th><th>Room</th><th>Check in</th><th>Check out</th></tr></thead><tbody>@forelse($stays->take(2) as $r)<tr><td><a href="/app/reservations?q={{ urlencode($r->reference) }}">{{ $r->guest_name }}</a></td><td>{{ $r->room->number }}</td><td>{{ $r->check_in->format('d M') }}</td><td>{{ $r->check_out->format('d M') }}</td></tr>@empty<tr><td colspan="4" class="empty">No {{ $tab }} due. View all bookings to plan the next stay.</td></tr>@endforelse</tbody></table></div></div>@endforeach
 </section>
</div>
@endsection

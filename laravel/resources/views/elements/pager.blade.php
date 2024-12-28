<?php $route_params = $route_params ?? []; ?>

<p>{{ number_format($paginator->total()) }}件</p>

<ul class="pagination">
    @if ($paginator->lastPage() > 1)
    <?php
    $min_page = max($paginator->currentPage() - 5, 1);
    $max_page = min($min_page + 9, $paginator->lastPage());
    $min_page = max(min($max_page - 9, $min_page), 1);
    ?>

    @if ($paginator->currentPage() > 1)
    <li>{!! Tag::link(route($route_name, array_merge($route_params, ['page' => 1])), '<<') !!}</li>
    <li class="previous">{!! Tag::link(route($route_name, array_merge($route_params, ['page' => $paginator->currentPage() - 1])), '<') !!}</li>
    @else
    <li class="disabled"><a href="#" tag="li">&lt;&lt;</a></li>
    <li class="previous disabled"><a href="#">&lt;</a></li>
    @endif

    {!! ($min_page > 2) ? '<li class="disabled"><a href="#">...</a></li>' : '' !!}

    @for ($i = $min_page; $i <= $max_page; $i++)
    @if ($paginator->currentPage() == $i)
    <li class="active"><a href="#">{{ $paginator->currentPage() }}</a></li>
    @else
    <li>{!! Tag::link(route($route_name, array_merge($route_params, ['page' => $i])), $i) !!}</li>
    @endif
    @endfor

    {!! ($max_page < ($paginator->lastPage() - 1)) ? '<li class="disabled"><a href="#">...</a></li>' : '' !!}

    @if (($paginator->currentPage() < $paginator->lastPage()))
    <li class="next">{!! Tag::link(route($route_name, array_merge($route_params, ['page' => $paginator->currentPage() + 1])), '>') !!}</li>
    <li>{!! Tag::link(route($route_name, array_merge($route_params, ['page' => $paginator->lastPage()])), '>>') !!}</li>
    @else
    <li class="next disabled"><a href="#">&gt;</a></li>
    <li class="disabled"><a href="#">&gt;&gt;</a></li>
    @endif

    @endif
</ul>

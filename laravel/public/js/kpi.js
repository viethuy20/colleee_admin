$(document).ready(function ($) {
    /** start setup **/
    let thisYear = (new Date()).getFullYear();
    let yearEnd = 2020;
    let thisMonth = (new Date()).getMonth();
    let m_names = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
    let $label = $('.item_select').find(':selected').text();
    $('.table-year').hide();
    $('.table-call-ajax').hide();
    showYearAndMonth();
    let thisDay = [];
    var day_curent = (new Date()).getDate();
    for (let i = 1; i <= day_curent; i++) {
        thisDay.push(i);
    }
    if (typeof $('#numberUserLogin').val() !== "undefined") {
        chartGrapDay(JSON.parse($('#numberUserLogin').val()), thisDay, '', $label);
    }

    /** start handle btn **/
    var header = document.getElementById("pageNav");
    if (header) {
        var btn = header.getElementsByClassName("btn");
        for (var i = 0; i < btn.length; i++) {
            btn[i].addEventListener("click", function (e) {
                var current = header.getElementsByClassName("active");
                current[0].className = current[0].className.replace(" active", "");
                this.className += " active";

                //show | hidden button month
                if ($(".year").hasClass("active")) {
                    $('.month_select').hide();
                } else {
                    $('.month_select').show();
                }
            });
        }
    }


    /** start show year and month select **/
    function showYearAndMonth() {
        $('.month_select').find('option').remove();
        $('.year_select').find('option').remove();
        for (var f = thisMonth; f >= 0; f--) {
            var m = m_names[0 + f].slice(0, 3);
            $('<option>', {value: m, text: m + '月'}).appendTo(".month_select");

        }
        for (var i = thisYear; i >= yearEnd; i--) {
            var year = i;
            $('<option>', {value: year, text: year + '年'}).appendTo(".year_select");
        }
    }

    /** start click button select year **/
    $(document).on('change', '.year_select', function () {
        $('.table-month-year').hide();
        $('.table-year').hide();
        $('.table-call-ajax').show();
        if ($(this).val() < thisYear) {
            $('.month_select').find('option').remove();
            for (var j = 0; j <= 11; j++) {
                var months = m_names[0 + j].slice(0, 3);
                $('<option>', {value: months, text: months + '月'}).appendTo(".month_select");
            }
        } else {
            $('.month_select').find('option').remove();
            for (var f = thisMonth; f >= 0; f--) {
                var m = m_names[0 + f].slice(0, 3);
                $('<option>', {value: m, text: m + '月'}).appendTo(".month_select");
            }
        }
        day = [];
        var yearSelect = $('.year_select').find(':selected').val();
        var monthSelect = $('.month_select').find(':selected').val();
        var itemSelect =$('.item_select').find(':selected').val();
        var $label = $('.item_select').find(':selected').text();
        var  $p = (itemSelect == 'unique_login_totals' || itemSelect == 'created_totals') ? '%' : '';
        var day_end = daysInMonth(monthSelect,yearSelect);
        var countYear = 11;
        var type_item = 1;
        if ($('li .week').hasClass('active')) {
            type_item = 2;
        } else if($('li .year').hasClass('active')) {
            type_item = 3;
        }
        if (thisYear == yearSelect && monthSelect == thisMonth+1) {
            day_end =  (new Date()).getDate();
            countYear = thisMonth;
        }
        for (var i = 1; i <= day_end; i++) {
            day.push(i);
        }

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: '/users/getDataGrap',
            type: 'POST',
            dataType: 'json',
            data: {year_select: yearSelect, month_select: monthSelect, item_select: itemSelect, type_item: type_item},
            success: function (res) {
                $format = new Intl.NumberFormat('de-DE');
                var unique_login_totals = '-';
                var created_totals = '-';
                if (parseInt(res.kpi.unique_login_total) > 0) {
                    unique_login_totals = (res.kpi.unique_action_total * 100.0) / res.kpi.unique_login_total;
                }
                if (parseInt(res.kpi.created_total)) {
                    created_totals = (res.kpi.created_action_total * 100.0) / res.kpi.created_total;
                }
                $('.login_total').text($format.format(res.kpi.login_total));
                $('.unique_login_total').text($format.format(res.kpi.unique_login_total));
                $('.unique_action_total').text($format.format(res.kpi.unique_action_total));
                $('.unique_login_totals').text(addZeroes(unique_login_totals) + ' %');
                $('.created_total').text($format.format(res.kpi.created_total));
                $('.created_action_total').text($format.format(res.kpi.created_action_total));
                $('.created_totals').text(addZeroes(created_totals) + ' %');
                $('.prohibited_total').text($format.format(res.kpi.prohibited_total));
                $('.deleted_total').text($format.format(res.kpi.deleted_total));
                if ($('.day').hasClass('active')) {
                    $('#dayChart').remove()
                    $('#grap-kpi').prepend(' <canvas id="dayChart"></canvas>');
                    chartGrapDay(JSON.parse(res.dataGrapDay), day, $p, $label);
                } else if ($('.week').hasClass('active')) {
                    $('#weekChart').remove();
                    $('#grap-kpi').prepend('<canvas id="weekChart"></canvas>');
                    chartGrapWeek(JSON.parse(res.dataGrapWeek), getWeeksInMonth(yearSelect, monthSelect), $p, $label);
                } else if ($('.year').hasClass('active')) {
                    $('#monthChart').remove();
                    $('#grap-kpi').append(' <canvas id="monthChart"></canvas>');
                    chartGrapMonth(JSON.parse(res.dataGrapYear), countYear, $p, $label);
                }
            }
        });
    });

    /** start click button select month **/
    $(document).on('change', '.month_select', function (event) {
        $('.table-month-year').hide();
        $('.table-year').hide();
        $('.table-call-ajax').show();
        day = [];
        var yearSelect = $('.year_select').find(':selected').val();
        var monthSelect = $('.month_select').find(':selected').val();
        var itemSelect = $('.item_select').find(':selected').val();
        var $label = $('.item_select').find(':selected').text();
        var  $p = (itemSelect == 'unique_login_totals' || itemSelect == 'created_totals') ? '%' : '';
        var day_end = daysInMonth(monthSelect,yearSelect);
        var countYear = 11;
        if (thisYear == yearSelect && monthSelect == thisMonth+1) {
            day_end =  (new Date()).getDate();
            countYear = thisMonth;
        }
        for (let i = 1; i <= day_end; i++) {
            day.push(i);
        }

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: '/users/getDataGrap',
            type: 'POST',
            dataType: 'json',
            data: {year_select: yearSelect, month_select: monthSelect, item_select: itemSelect},
            success: function (res) {
                $format = new Intl.NumberFormat('de-DE');
                var unique_login_totals = '-';
                var created_totals = '-';
                if (parseInt(res.kpi.unique_login_total) > 0) {
                    unique_login_totals = (res.kpi.unique_action_total * 100.0) / res.kpi.unique_login_total;
                }
                if (parseInt(res.kpi.created_total)) {
                    created_totals = (res.kpi.created_action_total * 100.0) / res.kpi.created_total;
                }
                $('.login_total').text($format.format(res.kpi.login_total));
                $('.unique_login_total').text($format.format(res.kpi.unique_login_total));
                $('.unique_action_total').text($format.format(res.kpi.unique_action_total));
                $('.unique_login_totals').text(addZeroes(unique_login_totals) + ' %');
                $('.created_total').text($format.format(res.kpi.created_total));
                $('.created_action_total').text($format.format(res.kpi.created_action_total));
                $('.created_totals').text(addZeroes(created_totals) + ' %');
                $('.prohibited_total').text($format.format(res.kpi.prohibited_total));
                $('.deleted_total').text($format.format(res.kpi.deleted_total));

                //show data chart line
                if ($('.day').hasClass('active')) {
                    $('#dayChart').remove()
                    $('#grap-kpi').prepend(' <canvas id="dayChart"></canvas>');
                    chartGrapDay(JSON.parse(res.dataGrapDay), day, $p, $label);
                } else if ($('.week').hasClass('active')) {
                    $('#weekChart').remove();
                    $('#grap-kpi').prepend('<canvas id="weekChart"></canvas>');
                    chartGrapWeek(JSON.parse(res.dataGrapWeek), getWeeksInMonth(yearSelect, monthSelect), $p, $label);
                } else if ($('.year').hasClass('active')) {
                    $('#monthChart').remove();
                    $('#grap-kpi').append(' <canvas id="monthChart"></canvas>');
                    chartGrapMonth(JSON.parse(res.dataGrapYear), countYear, $p, $label);
                }
            }
        });
    });

    /** start click button select item **/
    $(document).on('change', '.item_select', function (event) {
        var day = [];
        var yearSelect = $('.year_select').find(':selected').val();
        var monthSelect = $('.month_select').find(':selected').val();
        var itemSelect =$('.item_select').find(':selected').val();
        var $label = $('.item_select').find(':selected').text();
        var day_end = daysInMonth(monthSelect,yearSelect);
        var  $p = (itemSelect == 'unique_login_totals' || itemSelect == 'created_totals') ? '%' : '';
        var type_item = 1;
        if ($('li .week').hasClass('active')) {
            type_item = 2;
        } else if($('li .year').hasClass('active')) {
            type_item = 3;
        }
        var countYear = 11;
        if (thisYear == yearSelect && monthSelect == thisMonth+1) {
            day_end =  (new Date()).getDate();
            countYear = thisMonth;
        }
        for (var i = 1; i <= day_end; i++) {
            day.push(i);
        }

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url: '/users/getDataGrap',
            type: 'POST',
            dataType: 'json',
            data: {year_select: yearSelect, month_select: monthSelect,item_select: itemSelect, type_item: type_item },
            success: function (res) {
                if ($('.day').hasClass('active')) {
                    $('#dayChart').remove()
                    $('#grap-kpi').prepend(' <canvas id="dayChart"></canvas>');
                    chartGrapDay(JSON.parse(res.dataGrapDay), day, $p, $label);
                } else if ($('.week').hasClass('active')) {
                    $('#weekChart').remove();
                    $('#grap-kpi').prepend('<canvas id="weekChart"></canvas>');
                    chartGrapWeek(JSON.parse(res.dataGrapWeek), getWeeksInMonth(yearSelect, monthSelect), $p, $label);
                } else if ($('.year').hasClass('active')) {
                    $('#monthChart').remove();
                    $('#grap-kpi').append(' <canvas id="monthChart"></canvas>');
                    chartGrapMonth(JSON.parse(res.dataGrapYear), parseInt(countYear) - 1, $p, $label);
                }
            }
        });
    });

    /** start click button day **/
    $(document).on('click', '.day', function (event) {
        $('#weekChart').hide();
        $('#monthChart').hide();
        $('.table-month-year').show();
        $('.table-year').hide();
        $('.table-call-ajax').hide();
        showYearAndMonth()
        $(".item_select").prop('selectedIndex', 0);
        const data = JSON.parse($('#numberUserLogin').val());
        var $label = $('.item_select').find(':selected').text();
        $('#dayChart').remove()
        $('#grap-kpi').prepend(' <canvas id="dayChart"></canvas>');
        chartGrapDay(data, thisDay, '', $label);
    });

    /** start click button week **/
    $(document).on('click', '.week', function (event) {
        $('#dayChart').hide();
        $('#monthChart').hide();
        $('.table-month-year').show();
        $('.table-year').hide();
        $('.table-call-ajax').hide();
        showYearAndMonth()
        $(".item_select").prop('selectedIndex', 0);
        const data = JSON.parse($('#weekUserLogin').val());
        var $label = $('.item_select').find(':selected').text();
        const weeks = getWeeksInMonth($(".year_select option:selected").val(), $(".month_select option:selected").val());
        $('#weekChart').remove()
        $('#grap-kpi').prepend(' <canvas id="weekChart"></canvas>');
        chartGrapWeek(data, weeks, '', $label);
    });

    /** start click button year **/
    $(document).on('click', '.year', function (event) {
        $('#dayChart').hide();
        $('#weekChart').hide();
        $('.table-month-year').hide();
        $('.table-year').show();
        $('.table-call-ajax').hide();
        showYearAndMonth();
        $(".item_select").prop('selectedIndex', 0);
        var $label = $('.item_select').find(':selected').text();
        const data = JSON.parse($('#yearUserLogin').val());
        const month = (new Date()).getMonth();
        $('#monthChart').remove()
        $('#grap-kpi').prepend(' <canvas id="monthChart"></canvas>');
        chartGrapMonth(data, month, '', $label);
    });

    /** function common **/
    function getWeeksInMonth(year, month) {
        const weeks = [],
            firstDate = new Date(year, month - 1, 0),
            lastDate = new Date(year, month, 0),
            numDays = lastDate.getDate();

        let dayOfWeekCounter = firstDate.getDate();

        for (let date = 1; date <= numDays; date++) {
            if (dayOfWeekCounter === 0 || weeks.length === 0) {
                weeks.push([]);
            }
            weeks[weeks.length - 1].push(date);
            dayOfWeekCounter = (dayOfWeekCounter + 1) % 7;
        }

        return weeks;
    }

    function chartGrapDay($data, $day, $p, $label) {
        let labels = $day;
        let datas = {
            labels: labels,
            datasets: [{
                label: $label,
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(255, 99, 132)',
                data: $data,
            }]
        };

        const config = {
            type: 'line',
            data: datas,
            options: {
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        filter: ctx => ctx.datasetIndex == 0,
                        callbacks: {
                            label: ctx => {
                                var value = ctx.dataset.data[ctx.dataIndex];
                                return $label + ' : ' + value + ' ' + $p;
                            },
                        }
                    }
                }
            }
        };

        const myChart = new Chart(
            document.getElementById('dayChart'),
            config
        );
    }

    function chartGrapWeek($data, $week, $p, $label) {
        let week_lables = [];
        let data = $data;
        let weeks = $week;

        for (var i = 1; i <= weeks.length; i++) {
            week_lables.push(i);
        }
        let labels = week_lables;

        // labels = day;
        let datas = {
            labels: labels,
            datasets: [{
                label: $label,
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(255, 99, 132)',
                data: data,
            }]
        };

        const config = {
            type: 'line',
            data: datas,
            options: {
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        filter: ctx => ctx.datasetIndex == 0,
                        callbacks: {
                            label: ctx => {
                                var value = ctx.dataset.data[ctx.dataIndex];
                                return $label + ' : ' + value + ' ' + $p;
                            },
                        }
                    }
                }
            }
        };
        const weekChart = new Chart(
            document.getElementById('weekChart'),
            config
        );
    }

    function chartGrapMonth($data, $month, $p, $label) {
        let year_lables = [];
        let data = $data;
        let month = $month;

        for (let i = 1; i <= parseInt(month) + 1; i++) {
            year_lables.push(i);
        }
        let labels = year_lables;

        // labels = day;
        let datas = {
            labels: labels,
            datasets: [{
                label: $label,
                backgroundColor: 'rgb(255, 99, 132)',
                borderColor: 'rgb(255, 99, 132)',
                data: data,
            }]
        };

        const config = {
            type: 'line',
            data: datas,
            options: {
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        filter: ctx => ctx.datasetIndex == 0,
                        callbacks: {
                            label: ctx => {
                                var value = ctx.dataset.data[ctx.dataIndex];
                                return $label + ' : ' + value + ' ' + $p;
                            },
                        }
                    }
                }
            }
        };

        const monthChart = new Chart(
            document.getElementById('monthChart'),
            config
        );
    }

    function daysInMonth (month, year) {
        return new Date(year, month, 0).getDate();
    }

    function addZeroes(num) {
        return num.toLocaleString("en", {useGrouping: false, minimumFractionDigits: 3})
    }

});

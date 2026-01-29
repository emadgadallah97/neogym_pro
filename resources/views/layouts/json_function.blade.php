<script>
    // $(document).ready(function() {
    //     $('select[name="id_country"]').on('change', function() {
    //         var id_country = $(this).val();
    //         if (id_country) {
    //             $.ajax({
    //                 url: "{{ URL::to('get_governmentes') }}/" + id_country,
    //                 type: "GET",
    //                 dataType: "json",
    //                 success: function(data) {
    //                     $('select[name="id_governmentes"]').empty();
    //                     $('select[name="id_city"]').empty();
    //                     $('select[name="id_area"]').empty();
    //                     $('select[name="id_area"]').append('<option value="" selected disabled>{{ trans('patient_trans.select_option') }}</option>');
    //                     $('select[name="id_city"]').append('<option value="" selected disabled>{{ trans('patient_trans.select_option') }}</option>');
    //                     $('select[name="id_governmentes"]').append('<option value="" selected disabled>{{ trans('patient_trans.select_option') }}</option>');
    //                     $.each(data, function(key, value) {
    //                         $('select[name="id_governmentes"]').append('<option value="' +
    //                         key + '">' + value + '</option>');
    //                     });
    //                 },
    //             });

    //         } else {
    //             console.log('AJAX load did not work');
    //         }
    //     });

    // });
    function get_city(id_governmentes)
     {
           var id_governmentes =id_governmentes;
            if (id_governmentes) {
                              $.ajax({
                                    url: "{{ URL::to('get_city') }}/" + id_governmentes,
                                    type: "GET",
                                    dataType: "json",
                                    success: function(data) {
                                        $('select[name="id_city"]').empty();
                                        $('select[name="id_area"]').empty();
                                        $('select[name="id_area"]').append('<option value="" selected disabled>{{ trans('patient_trans.select_option') }}</option>');
                                        $('select[name="id_city"]').append('<option value="" selected disabled>{{ trans('patient_trans.select_option') }}</option>');
                                        $.each(data, function(key, value) {
                                            $('select[name="id_city"]').append('<option value="' + key + '">' + value + '</option>');
                                        });
                                    },
                                });

                            } else {
                                console.log('AJAX load did not work');
                            }
        }
     function get_area(id_city)
                    {
                        var id_city =id_city;
                        if (id_city)
                             {
                            $.ajax
                            ({
                                url: "{{ URL::to('get_area') }}/" + id_city,
                                type: "GET",
                                dataType: "json",
                                success: function(data)
                                {
                                     $('select[name="id_area"]').empty();
                                     $('select[name="id_area"]').append('<option value="" selected disabled>{{ trans('patient_trans.select_option') }}</option>');
                                    $.each(data, function(key, value)
                                    {
                                        $('select[name="id_area"]').append('<option value="' +
                                        key + '">' + value + '</option>');
                                    });
                                 },
                            });

                              }
                              else
                              {
                                 console.log('AJAX load did not work');
                              }
                    }

</script>

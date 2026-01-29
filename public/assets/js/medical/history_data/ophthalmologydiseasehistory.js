$(document).ready(function () {
    // Autocomplete for Complaint Category
    setupAutocomplete("#diagnosis", "#diagnosislist", autocompleteUrl);
});
$(document).ready(function () {
    let table = $("#diagnosisTable").DataTable({
        processing: true,
        serverSide: true,
        ajax: diseaseHistoryIndexUrl,
        columns: [
            { data: "id_patient", name: "id_patient" },
            { data: "id_visit", name: "id_visit" },
            { data: "id_reservation", name: "id_reservation" },
            { data: "type_visit", name: "type_visit" },
            { data: "diseases", name: "diseases" },
            { data: "status", name: "status" },
            { data: "eye", name: "eye" },
            { data: "for_number", name: "for_number" },
            { data: "for_period", name: "for_period" },
            { data: "remarks", name: "remarks" },
            { data: "created_by", name: "created_by" },
            { data: "created_at", name: "created_at" },
            {
                data: "action",
                name: "action",
                orderable: false,
                searchable: false,
            },
        ],
    });

    $("#addDiseaseBtn").on("click", function () {
        let data = {
          
            diseases: $("#diseases").val(),
            status: $("#status").val(),
            eye: $(".btn-group [data-eye].active").data("eye"),
            for_number: $("#forNumber").val(),
            for_period: $(".btn-group [data-period].active").data("period"),
            remarks: $("#remarks").val(),
        };

        $.ajax({
            url: diseaseHistoryStoreUrl,
            method: "POST",
            data: {
                _token: csrfToken,
                ...data,
            },
            success: function (response) {
                Swal.fire("Success", response.success, "success");
                table.ajax.reload();
                $("#diseaseForm")[0].reset();
            },
            error: function (xhr) {
                Swal.fire("Error", xhr.responseJSON.message, "error");
            },
        });
    });

    $(document).on("click", ".deleteRecord", function () {
        let id = $(this).data("id");

        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/disease-history/${id}`,
                    method: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (response) {
                        Swal.fire("Deleted!", response.success, "success");
                        table.ajax.reload();
                    },
                    error: function () {
                        Swal.fire("Error", "Failed to delete record", "error");
                    },
                });
            }
        });
    });

    $(".btn-group [data-eye], .btn-group [data-period]").on(
        "click",
        function () {
            $(this).siblings().removeClass("active");
            $(this).addClass("active");
        }
    );
});

function setupAutocomplete(inputId, suggestionsId, optionsOrUrl) {
    let timer = null;
    let suggestionsBox = $(suggestionsId);

    $(inputId).on("input", function () {
        clearTimeout(timer);
        let query = $(this).val();

        if (query.length > 0) {
            timer = setTimeout(function () {
                if (typeof optionsOrUrl === "string") {
                    // Fetch data from the server using AJAX
                    $.ajax({
                        url: optionsOrUrl,
                        type: "GET",
                        data: {
                            query: query,
                        },
                        success: function (data) {
                            suggestionsBox.empty();
                            if (data.length > 0) {
                                data.forEach(function (item) {
                                    suggestionsBox.append(
                                        `<a href="#" class="list-group-item list-group-item-action">${item}</a>`
                                    );
                                });
                                suggestionsBox.show();
                            } else {
                                suggestionsBox.hide();
                            }
                        },
                    });
                } else {
                    // Use local options
                    let filteredOptions = optionsOrUrl.filter(function (
                        option
                    ) {
                        return option
                            .toLowerCase()
                            .includes(query.toLowerCase());
                    });

                    suggestionsBox.empty();
                    if (filteredOptions.length > 0) {
                        filteredOptions.forEach(function (item) {
                            suggestionsBox.append(
                                `<a href="#" class="list-group-item list-group-item-action">${item}</a>`
                            );
                        });
                        suggestionsBox.show();
                    } else {
                        suggestionsBox.hide();
                    }
                }
            }, 300);
        } else {
            suggestionsBox.hide();
        }
    });

    // Double click to show all options
    $(inputId).on("dblclick", function () {
        if (typeof optionsOrUrl === "string") {
            // Fetch all data from the server using AJAX
            $.ajax({
                url: optionsOrUrl,
                type: "GET",
                data: {
                    query: "",
                }, // Empty query to fetch all
                success: function (data) {
                    suggestionsBox.empty();
                    if (data.length > 0) {
                        data.forEach(function (item) {
                            suggestionsBox.append(
                                `<a href="#" class="list-group-item list-group-item-action">${item}</a>`
                            );
                        });
                        suggestionsBox.show();
                    } else {
                        suggestionsBox.hide();
                    }
                },
            });
        } else {
            // Use local options
            suggestionsBox.empty();
            optionsOrUrl.forEach(function (item) {
                suggestionsBox.append(
                    `<a href="#" class="list-group-item list-group-item-action">${item}</a>`
                );
            });
            suggestionsBox.show();
        }
    });

    suggestionsBox.on("click", "a", function (e) {
        e.preventDefault();
        $(inputId).val($(this).text());
        suggestionsBox.hide();
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest(inputId + ", " + suggestionsId).length) {
            suggestionsBox.hide();
        }
    });
}

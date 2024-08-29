//next and Previous action Validation logic for the current step

jQuery(document).ready(function ($) {
    var currentStep = 1;

    function showStep(step) {
        $('.step').hide();
        $('.step-' + step).show();
    }
    showStep(currentStep);

    function validateStep() {
        let isValid = true;
        $(".error-message").text(""); 

        if ($("#first_name").val().trim() === "") {
            $("#first_name_error").text("First Name is required");
            isValid = false;
        }

        if ($("#last_name").val().trim() === "") {
            $("#last_name_error").text("Last Name is required");
            isValid = false;
        }

        const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailPattern.test($("#email").val().trim())) {
            $("#email_error").text("Invalid Email");
            isValid = false;
        }

        if ($("#address").val().trim() === "") {
            $("#address_error").text("Address is required");
            isValid = false;
        }

        if ($("#country").val().trim() === "") {
            $("#country_error").text("Country is required");
            isValid = false;
        }

        if ($("#state").val().trim() === "") {
            $("#state_error").text("State is required");
            isValid = false;
        }

        if ($("#city").val().trim() === "") {
            $("#city_error").text("City is required");
            isValid = false;
        }

        if ($("#company_name").val().trim() === "") {
            $("#company_name_error").text("Company Name is required");
            isValid = false;
        }

        $('#company_address_container input[name="company_address[]"]').each(function () {
            if ($(this).val().trim() === "") {
                $(this).next(".error-message").text("Company Address is required");
                isValid = false;
            }
        });

        if ($("#card_number").val().trim() === "") {
            $("#card_number_error").text("Card Number is required");
            isValid = false;
        }

        if ($("#expiry_date").val().trim() === "") {
            $("#expiry_date_error").text("Expiry Date is required");
            isValid = false;
        }

        if ($("#cvv").val().trim() === "") {
            $("#cvv_error").text("CVV is required");
            isValid = false;
        }
        return isValid;
    }
    $('.next').on('click', function () {      
            var isValid = true;
            $('.step-' + currentStep + ' input').each(function () {
                if ($(this).val() === '') {
                    isValid = false;
                    validateStep();
                    return false;
                }
            });           
            if (isValid) {
                currentStep++;
                showStep(currentStep);
            }
      
    });

    $('.prev').on('click', function () {
        currentStep--;
        showStep(currentStep);
    });

//insert data in custom post type
    $('#multi-step-form').on('submit', function (event) {
        event.preventDefault(); 

        $('#submit-btn').prop('disabled', true);
        $('#loader').show();

        var formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: formData + '&action=submit_form',
            success: function (response) {
                response = JSON.parse(response);

                if (response.status === 'success') {
                   
                    $('#multi-step-form')[0].reset(); // Reset the form on success
                    $('#message').html('<p>' + response.message + '</p>');
                } else {
                 
                    $('#message').html('<p>' + response.message + '</p>');
                }

                // Hide loader and enable submit button
                $('#loader').hide();
                $('#submit-btn').prop('disabled', false);
            },
            error: function () {
                alert('There was an error submitting the form.');

                // Hide loader and enable submit button
                $('#loader').hide();
                $('#submit-btn').prop('disabled', false);
            }
        });
    });

    // add another company address functionalty
    $('#company_address_container').on('click', '.add_address', function () {
        $('#company_address_container').append(
            '<div class="company_address">' +
            '<label for="company_address">Company Address</label>' +
            '<input type="text" name="company_address[]" required>' +
            '<button type="button" class="remove_address">Remove Address</button>' +
            '</div>'
        );
    });

    $('#company_address_container').on('click', '.remove_address', function () {
        $(this).closest('.company_address').remove();
    });
});

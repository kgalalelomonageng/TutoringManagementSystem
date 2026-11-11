/* ==================================
   REGISTRATION VALIDATION
================================== */

function validateRegistration()
{
    let firstName =
    document.getElementById("first_name").value;

    let lastName =
    document.getElementById("last_name").value;

    let email =
    document.getElementById("email").value;

    let phone =
    document.getElementById("phone").value;

    let password =
    document.getElementById("password").value;

    if(firstName.trim() === "")
    {
        alert("First Name is required.");
        return false;
    }

    if(lastName.trim() === "")
    {
        alert("Last Name is required.");
        return false;
    }

    let emailPattern =
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if(!emailPattern.test(email))
    {
        alert("Enter a valid email address.");
        return false;
    }

    let phonePattern =
    /^[0-9]{10}$/;

    if(!phonePattern.test(phone))
    {
        alert("Phone number must contain 10 digits.");
        return false;
    }

    if(password.length < 8)
    {
        alert("Password must be at least 8 characters.");
        return false;
    }

    return true;
}

/* ==================================
   PASSWORD STRENGTH
================================== */

function checkPasswordStrength()
{
    let password =
    document.getElementById("password").value;

    let strength =
    document.getElementById("strength");

    if(password.length < 8)
    {
        strength.innerHTML = "Weak";
        strength.style.color = "red";
    }
    else if(password.length < 12)
    {
        strength.innerHTML = "Medium";
        strength.style.color = "orange";
    }
    else
    {
        strength.innerHTML = "Strong";
        strength.style.color = "green";
    }
}

/* ==================================
   PAYMENT VALIDATION
================================== */

function validatePayment()
{
    let programme =
    document.getElementById("programme_id").value;

    if(programme === "")
    {
        alert("Select a tutoring programme.");
        return false;
    }

    return true;
}

/* ==================================
   BOOKING VALIDATION
================================== */

function validateBooking()
{
    let bookingDate =
    document.getElementById("booking_date").value;

    if(bookingDate === "")
    {
        alert("Select an available weekday.");
        return false;
    }

    let selectedDate = new Date(bookingDate + "T12:00:00");

    let day =
    selectedDate.getDay();

    if(day === 0 || day === 6)
    {
        alert(
        "Bookings are only allowed Monday to Friday."
        );

        return false;
    }

    return true;
}

/* ==================================
   TUTOR AVAILABILITY
================================== */

function validateAvailability()
{
    let status =
    document.getElementById(
    "availability_status"
    ).value;

    if(status === "")
    {
        alert(
        "Select an availability status."
        );

        return false;
    }

    return true;
}

/* ==================================
   DELETE CONFIRMATION
================================== */

function confirmDelete()
{
    return confirm(
    "Are you sure you want to delete this record?"
    );
}

/* ==================================
   STUDENT SEARCH
================================== */

function searchStudent()
{
    let input =
    document.getElementById("search");

    let filter =
    input.value.toUpperCase();

    let table =
    document.getElementById("studentTable");

    let tr =
    table.getElementsByTagName("tr");

    for(let i = 0; i < tr.length; i++)
    {
        let td =
        tr[i].getElementsByTagName("td")[1];

        if(td)
        {
            let txtValue =
            td.textContent || td.innerText;

            if(
            txtValue.toUpperCase()
            .indexOf(filter) > -1
            )
            {
                tr[i].style.display = "";
            }
            else
            {
                tr[i].style.display = "none";
            }
        }
    }
}

/* ==================================
   AUTO DATE DISPLAY
================================== */

window.onload = function()
{
    let dateBox =
    document.getElementById("todayDate");

    if(dateBox)
    {
        let date =
        new Date();

        dateBox.innerHTML =
        date.toDateString();
    }

    const registrationForm = document.querySelector('form[action="../php/register.php"]');
    if(registrationForm)
    {
        registrationForm.addEventListener("submit", function(event)
        {
            if(!validateRegistration())
            {
                event.preventDefault();
            }
        });
    }

    const paymentForm = document.getElementById("paymentForm");
    if(paymentForm)
    {
        paymentForm.addEventListener("submit", function(event)
        {
            if(!validatePayment())
            {
                event.preventDefault();
            }
        });
    }

    const bookingForm = document.querySelector('form[action="book_session.php"]');
    if(bookingForm)
    {
        bookingForm.addEventListener("submit", function(event)
        {
            if(!validateBooking())
            {
                event.preventDefault();
            }
        });
    }

    const availabilityForm = document.getElementById("availabilityForm");
    if(availabilityForm)
    {
        availabilityForm.addEventListener("submit", function(event)
        {
            if(!validateAvailability())
            {
                event.preventDefault();
            }
        });
    }

    const slotData = document.getElementById("availableSlots");
    const programmeSelect = document.getElementById("programme_id");
    const dateSelect = document.getElementById("booking_date");
    const timeSelect = document.getElementById("booking_time");

    if(slotData && programmeSelect && dateSelect && timeSelect)
    {
        const availableSlots = JSON.parse(slotData.textContent);

        function setOptions(select, placeholder, options)
        {
            select.replaceChildren(new Option(placeholder, ""));
            options.forEach(function(option)
            {
                select.add(new Option(option.label, option.value));
            });
            select.disabled = options.length === 0;
        }

        function updateDates()
        {
            const programmeId = Number(programmeSelect.value);
            const programmeSlots = availableSlots.filter(function(slot)
            {
                return Number(slot.programme_id) === programmeId;
            });
            const dates = Array.from(new Set(programmeSlots.map(function(slot)
            {
                return slot.available_date;
            })));

            setOptions(dateSelect, "Select a date", dates.map(function(date)
            {
                return { value: date, label: date };
            }));
            setOptions(timeSelect, "Select a date first", []);
        }

        function updateTimes()
        {
            const programmeId = Number(programmeSelect.value);
            const selectedDate = dateSelect.value;
            const sessions = availableSlots.filter(function(slot)
            {
                return Number(slot.programme_id) === programmeId &&
                    slot.available_date === selectedDate;
            });

            const sessionsByTime = new Map();
            sessions.forEach(function(slot)
            {
                const time = slot.available_time.slice(0, 5);
                if(!sessionsByTime.has(time))
                {
                    sessionsByTime.set(time, []);
                }
                sessionsByTime.get(time).push(slot.tutor_name);
            });
            setOptions(timeSelect, "Select an available time", Array.from(sessionsByTime.entries()).map(function(entry)
            {
                const tutors = Array.from(new Set(entry[1])).join(", ");
                return { value: entry[0], label: entry[0] + " — " + tutors };
            }));
        }

        programmeSelect.addEventListener("change", updateDates);
        dateSelect.addEventListener("change", updateTimes);
    }
    let formMessage =
    document.getElementById("formMessage");

    if(formMessage)
    {
        let query =
        new URLSearchParams(window.location.search);

        let messages =
        {
            invalid_login: "Email or password is incorrect.",
            login_required: "Please log in to continue.",
            admin_required: "Please log in with an administrator account.",
            invalid_registration: "Check the registration details and try again.",
            email_exists: "An account already uses that email address.",
            registration_failed: "Registration could not be completed. Please try again.",
            registered: "Registration complete. You can now log in.",
            logged_out: "You have been logged out."
        };

        let key = query.get("error");
        let text = key ? messages[key] : null;

        if(query.get("registered") === "1")
        {
            text = messages.registered;
        }
        else if(query.get("logged_out") === "1")
        {
            text = messages.logged_out;
        }

        if(text)
        {
            formMessage.textContent = text;
            formMessage.hidden = false;
        }
    }
}

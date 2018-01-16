
<p>
    <label for="fullName">Vaše jméno (vyžadováno)</label>
    [text* name class:form-control id:fullName]
</p>

<p>
    <label for="email">E-mailová adresa (vyžadováno)</label>
    [email* email class:form-control id:email]
</p>

<p>
    <label for="contact-us-topic">Téma (vyžadováno)</label>
    [select* topic id:contact-us-topic class:form-control "Request Help" "Report a Bug" "Request a Feature" "Make a Suggestion" "Leave a Comment" "Request a Workshop / Meeting" "Other"]
</p>

<div id="workshop-meeting-items">
    <p>
        <label for="group-type">Typ skupiny (vyžadováno)</label>
        [select group-type id:group-type class:form-control "Class" "Department" "Office" "Club" "Individual (faculty only)"]
    </p>

    <p>
        <label for="reason-for-request">Důvod žádosti (vyžadováno)</label>
        [select reason-for-request id:reason-for-request class:form-control "Getting started/sign up" "Teaching users how to use course or other site" "ePortfolios" "Consultation" "Other (please specify)"]
    </p>

    <div id="other-details">
        <p>
            <label class="sr-only" for="other-details">Ostatní podrobnosti</label>
            [text other-details id:other-details class:form-control]
        </p>
    </div>

    <p>
        <label for="number-of-participants">Počet účastníků (vyžadováno)</label>
        [text number-of-participants id:number-of-participants class:form-control]
    </p>

    <p>
        <label for="estimated-time-needed">Odhadovaný potřebný čas (vyžadováno)</label>
        [text estimated-time-needed id:estimated-time-needed class:form-control]
    </p>

    <p>
        <label for="openlab-site">Webová stránka OpenLab (pokud existuje)</label><br />
        [text openlab-site id:openlab-site class:openlab-site]
    </p>

    <p>
        <label for="date-time-1st-choice">Datum / čas (první volba)</label>
        [text date-time-1st-choice id:date-time-1st-choice class:form-control]
    </p>

    <p>
        <label for="date-time-2nd-choice">Datum / čas (druhá volba)</label>
        [text date-time-2nd-choice id:date-time-2nd-choice class:form-control]
    </p>

    <p>
        <label for="date-time-3rd-choice">Datum / čas (třetí volba)</label>
        [text date-time-3rd-choice id:date-time-3rd-choice class:form-control]
    </p>

    <p>
        <label for="need-computer-lab">Potřebujete počítačovou laboratoř? Pokud ano, je odpovědný za rezervaci. (Povinný)</label><br />
        [radio_accessible need-computer-lab id:need-computer-lab "Yes" "No"]
    </p>
</div>


<p>
    <label for="question">Question</label>
    [textarea question class:form-control id:question]
</p>

[submit class:btn class:btn-primary "Submit"]

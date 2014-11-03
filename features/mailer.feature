Feature: Mailer
  Background:
    Given my client accepts content type "application/json"
    And I am connecting with HTTPS
    And I use the username "janus" with password "password"
    And there are users
      | username  | email                |
      | rh1       | rh389@my-email.ac.uk |
    And there are courses
      | id        | title                | level                | programme       |
      | 2892      | My course            | 5:Master of studies  | 25:Residential  |
      | 2893      | My course            | 5:Master of studies  | 25:Residential  |
      | 2894      | My course            | 4:Certificate        | 25:Residential  |
    And there are bookings
      | id      | username  | course_id |
      | 1       | rh1       | 2892      |
    And the bookings have items
      | booking_id  | code          | category   |
      | 1           | TUITION-2892  | 5:Tuition  |

  @mink:symfony2
  Scenario: Send a booking confirmation email
    When I post "resources/request-post-booking-confirmation.json" as "json" to "/api/mail"
    Then the response status code should be 200
    And I should get an email on "rh389@my-email.ac.uk" with:
    """
    We are pleased to confirm your place on the course below
    """

  @mink:symfony2
  Scenario: Send a booking confirmation email with attachments
    When I post "resources/request-post-booking-confirmation-with-attachments.json" as "json" to "/api/mail"
    Then the response status code should be 200
    And I should get an email on "rh389@my-email.ac.uk" with:
    """
    We are pleased to confirm your place on the course below
    """
    And I should get an email on "rh389@my-email.ac.uk" containing 2 attachments


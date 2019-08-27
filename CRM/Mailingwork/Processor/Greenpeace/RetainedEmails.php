<?php

class CRM_Mailingwork_Processor_Greenpeace_RetainedEmails extends CRM_Mailingwork_Processor_Base {

  /**
   * Fetch and process retained (on hold) email addresses
   *
   * @return array import results
   * @throws \Exception
   */
  public function import() {
    $activity_count = $email_count = 0;
    $lastHardbounceFrom = new DateTime(
      Civi::settings()->get('mailingwork_retained_emails_last_hardbounce_from')
    );
    while ($lastHardbounceFrom <= new DateTime()) {
      $lastHardbounceTo = clone $lastHardbounceFrom;
      $lastHardbounceTo->modify('+1 month');
      $retainedEmails = $this->client->api('bounce')->getRetainedEmailAddresses([
        'lastHardBounceFrom' => $lastHardbounceFrom->format('Y-m-d H:i:s'),
        'lastHardBounceTo'   => $lastHardbounceTo->format('Y-m-d H:i:s'),
      ]);
      if ($retainedEmails->total == 10000) {
        Civi::log()->error(
          "[Mailingwork/RetainedEmails] getRetainedEmailAddresses returned 10,000 addresses, there are likely missing records in time window " .
          "{$lastHardbounceFrom->format('Y-m-d H:i:s')} - {$lastHardbounceTo->format('Y-m-d H:i:s')}"
        );
      }
      foreach ($retainedEmails->data as $retainedEmail) {
        $email_count++;
        $email = $retainedEmail->address;
        if (empty($email)) {
          continue;
        }
        // this chained API call:
        //  - Puts all matching emails on hold
        //  - Creates a "Contact Updated" activity to log this action
        //  - Assigns the activity to the contact
        //  - Links the activity with the email address via ActivityContactEmail
        $result = civicrm_api3('Email', 'get', [
          'return'                          => ['id', 'contact_id'],
          'email'                           => $email,
          'on_hold'                         => FALSE,
          'api.Email.create'                => [
            'id'      => '$value.id',
            'on_hold' => TRUE
          ],
          'api.Activity.create'             => [
            'activity_type_id' => 'contact_updated',
            'subject'          => 'Email put on hold after too many bounces',
          ],
          'api.ActivityContact.create'      => [
            'activity_id'    => '$value.api.Activity.create.id',
            'contact_id'     => '$value.contact_id',
            'record_type_id' => 'Activity Targets',
          ],
          'api.ActivityContactEmail.create' => [
            'activity_contact_id' => '$value.api.ActivityContact.create.id',
            'email'               => $email,
          ],
        ]);
        $activity_count += $result['count'];
      }

      $lastHardbounceFrom = $lastHardbounceTo;
      if ($this->params['soft_limit'] > 0 && $email_count >= $this->params['soft_limit']) {
        break;
      }
    }
    return [
      'success'    => TRUE,
      'activities' => $activity_count,
      'emails'     => $email_count,
    ];
  }

}

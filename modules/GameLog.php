<?php


class GameLog extends APP_GameClass
{
    private $recipient;
    private $notif_type;
    private $message_parts;
    private $additional_variables;

    /**
     * GameLog constructor.
     *
     * @param $notif_type
     * @param $recipient
     */
    public function __construct($notif_type, $recipient)
    {
        $this->notif_type = $notif_type;
        $this->recipient = $recipient;
        $this->message_parts = [];
        $this->additional_variables = [];
    }

    public function sendLog()
    {
        $message_string = $this->getFinalMessageString();
        $vars = $this->getFinalMessageVariables();

//        echo "<pre>";
//        print_r($message_string);
//        echo "</pre>";
//
//        echo "<pre>";
//        print_r($vars);
//        echo "</pre>";

        if ($this->recipient == 'all') {
            hoipholqhuy::get()
                             ->notifyAllPlayers($this->notif_type, $message_string, $vars);
        }
    }

    public function addToMessage($string, $translatable_content = false, $variables = [])
    {
        $index = count($this->message_parts) + 1;
        $this->message_parts[$index]['string'] = $string;
        $this->message_parts[$index]['translatable_content'] = $translatable_content;
        $this->message_parts[$index]['vars'] = $variables;
    }

    public function addAdditionVariables($variables)
    {
        if (is_array($variables)) {
            foreach ($variables as $key => $val) {
                $this->additional_variables[$key] = $val;
            }
        }
    }

    private function getFinalMessageString()
    {
        $final_message = '';

        $count = 1;
        foreach ($this->message_parts as $index => $message_part) {
            if ($message_part['translatable_content'] == false) {
                $final_message .= $message_part['string'];
            } else {
                $final_message .= '${message_part_' . $count . '}';
            }
            $count++;
        }

        return $final_message;
    }

    private function getFinalMessageVariables()
    {

        $all_vars = [];
        foreach ($this->message_parts as $index => $message_part) {
            $vars = [];
            $args = [];
            foreach ($message_part['vars'] as $key => $val) {
                $vars[$key] = $val;
            }

            $args['i18n'] = array_keys($vars);
            foreach ($message_part['vars'] as $key => $val) {
                $args[$key] = $val;
            }

            $all_vars['message_part_' . $index] = [
                'log'  => $message_part['string'],
                'args' => $args,
            ];

        }

        foreach ($this->additional_variables as $key => $val) {
            $all_vars[$key] = $val;
        }

        return $all_vars;
    }
}

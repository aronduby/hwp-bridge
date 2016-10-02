<?php

class DumpFormatter extends \Monolog\Formatter\HtmlFormatter{
	
	public function format(array $record)
    {
        $output = $this->addTitle($record['level_name'], $record['level']);
        $output .= '<table cellspacing="1" width="100%">';

        $output .= $this->addRow('Message', (string) $record['message']);
        $output .= $this->addRow('Time', $record['datetime']->format($this->dateFormat));
        $output .= $this->addRow('Channel', $record['channel']);
        if ($record['context']) {
            $output .= $this->addRow('Context', D::ump($record['context'], D::S(D::OB|D::IGNORE_CLI)), false);
        }
        if ($record['extra']) {
            $output .= $this->addRow('Extra', D::ump($record['context'], D::S(D::OB|D::IGNORE_CLI)), false);
        }

        return $output.'</table>';
    }
}

?>
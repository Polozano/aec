<?php
class AECexport_json extends AECexport
{
	public function AECexport_json()
	{
		$this->lines = array();
	}

	public function finishExport()
	{
		if ( !empty( $this->description ) && !empty( $this->sum ) ) {
			$export = new stdClass();
			$export->description = $this->description;
			$export->data = $this->lines;
			$export->sum = $this->sum;

			echo json_encode( $export );
		} else {
			echo json_encode( $this->lines );
		}

		exit;
	}

}

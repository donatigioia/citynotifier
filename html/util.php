<?php
function __json_encode($data) {
        if (is_array ( $data ) || is_object ( $data )) {
                $islist = is_array ( $data ) && (empty ( $data ) || array_keys ( $data ) === range ( 0, count ( $data ) - 1 ));

                if ($islist) {
                        $json = '[' . implode ( ',', array_map ( '__json_encode', $data ) ) . ']';
                } else {
                        $items = Array ();
                        foreach ( $data as $key => $value ) {
                                $items [] = __json_encode ( "$key" ) . ':' . __json_encode ( $value );
                        }
                        $json = '{' . implode ( ',', $items ) . '}';
                }
        } elseif (is_string ( $data )) {
                $json = '"' . addcslashes ( $data, '\\"' ) . '"';
        } else {
                // int, floats, bools, null
                $json = strtolower ( var_export ( $data, true ) );
        }
        return $json;
}

function GET_check()
{
if (!isset($_GET['scope']) or !isset($_GET['type']) or !($_GET['lat']) or !isset($_GET['lng']) or !isset($_GET['radius']) or !isset($_GET['timemin']) or !isset($_GET['timemax']) or !isset($_GET['status']))
	return false;
else return true;
}
function POST_check($params)
{
if (!$params->type or !$params->lat or !$params->lng) return false;
else return true;
}

function NOTIFY_check($params)
{
if (!$params->event_id or !$params->lat or !$params->lng or !$params->status) return false;
else return true;
}
?>

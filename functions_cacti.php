<?php
function getTrees($DB)
{
	$output = array();
	$query = "SELECT id,name FROM graph_tree ORDER BY name ASC;";
	$result = mysqli_query($DB,$query);
	$count = $result->num_rows;
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc())
		{
			$row_id		= $row["id"];
			$row_name	= $row["name"];
#			print "$row_id = $row_name<br>\n";
			$output[$row_id] = $row_name;
		}
	}
	return $output;
}

function getTreeMembers($DB,$id)
{
	$output = array();
	$query = "SELECT graph_tree_items.id AS itemID,graph_tree_items.parent,graph_tree_items.title,graph_tree_items.host_id AS hostID,host.description,host.hostname FROM graph_tree_items LEFT JOIN host ON graph_tree_items.host_id = host.id WHERE graph_tree_id = '$id' ORDER BY position,graph_tree_items.id;";
	$result = mysqli_query($DB,$query);
	$count = $result->num_rows;
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc())
		{
			$row_item_id	= $row["itemID"];
			$row_parent	= $row["parent"];
			$row_title	= $row["title"];
			$row_host_id	= $row["hostID"];
			$row_host_name	= $row["description"];
			$row_host_addr	= $row["hostname"];
			$row_label	= $row_host_name;
			if ($row_host_id == "0") { $row_label = $row_title; }
			$output[$row_item_id] = array($row_host_id,$row_label,$row_host_name,$row_host_addr);
		}
	}
	return $output;
}

function getName($DB,$host_id)
{
	$query = "SELECT description FROM host WHERE id = '$host_id';";
	$result = mysqli_query($DB,$query);
	if ($result->num_rows == 1)
	{
		$row = $result->fetch_assoc();
		$name = $row["description"];
	}
	return $name;
}

function getGraphName($DB,$graph_id)
{
	$query = "SELECT graph_local.host_id, graph_templates.name FROM graph_local INNER JOIN graph_templates ON graph_local.graph_template_id=graph_templates.id WHERE graph_local.id = '$graph_id' ORDER BY graph_templates.name DESC;";
	$result = mysqli_query($DB,$query);
	if ($result->num_rows == 1)
	{
		$row = $result->fetch_assoc();
		$host_id	= $row["host_id"];
		$graph_name	= $row["name"];
	}
	return array($graph_name,$host_id);
}

function getGraphs($DB,$host_id)
{
	$output = array();
	$query = "SELECT graph_local.id, graph_templates.name FROM graph_local INNER JOIN graph_templates ON graph_local.graph_template_id=graph_templates.id WHERE host_id = '$host_id' ORDER BY graph_templates.name DESC;";
	$result = mysqli_query($DB,$query);
	$count = $result->num_rows;
	if ($result->num_rows > 0)
	{
		while($row = $result->fetch_assoc())
		{
			$row_id		= $row["id"];
			$row_templ_name	= $row["name"];
			$output[$row_id] = $row_templ_name;
		}
	}
	return $output;
}

function getGraphIndex($graphID)
{
	global $graphBasePath;
	$idx = 1;
	$filename = $graphBasePath . "graph_" . $graphID . "_" . $idx . ".png";
	if (!file_exists($filename)) { $idx = 5; }
	return $idx;
}

?>

<?php
/*
$Id$

  This code is part of LDAP Account Manager (http://www.ldap-account-manager.org/)
  Copyright (C) 2003 - 2010  Roland Gruber

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/


/**
* Configuration profile management.
*
* @package configuration
* @author Roland Gruber
*/


/** Access to config functions */
include_once('../../lib/config.inc');
/** Used to print status messages */
include_once('../../lib/status.inc');

// start session
if (strtolower(session_module_name()) == 'files') {
	session_save_path("../../sess");
}
@session_start();

setlanguage();

echo $_SESSION['header'];

?>

		<title>
			<?php
				echo _("Profile management");
			?>
		</title>
	<?php 
		// include all CSS files
		$cssDirName = dirname(__FILE__) . '/../../style';
		$cssDir = dir($cssDirName);
		while ($cssEntry = $cssDir->read()) {
			if (substr($cssEntry, strlen($cssEntry) - 4, 4) != '.css') continue;
			echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../../style/" . $cssEntry . "\">\n";
		}
	?>
		<link rel="shortcut icon" type="image/x-icon" href="../../graphics/favicon.ico">
	</head>
	<body>
		<table border=0 width="100%" class="lamHeader ui-corner-all">
			<tr>
				<td align="left" height="30">
					<a class="lamHeader" href="http://www.ldap-account-manager.org/" target="new_window">&nbsp;<img src="../../graphics/logo32.png" width=24 height=24 class="align-middle" alt="LDAP Account Manager">&nbsp;&nbsp;LDAP Account Manager</a>
				</td>
				<td align="right" height=20>
					<a href="conflogin.php"><IMG alt="configuration" src="../../graphics/undo.png">&nbsp;<?php echo _("Back to profile login") ?></a>
				</td>
			</tr>
		</table>
		<br>

<?php
// include all JavaScript files
$jsDirName = dirname(__FILE__) . '/../lib';
$jsDir = dir($jsDirName);
$jsFiles = array();
while ($jsEntry = $jsDir->read()) {
	if (substr($jsEntry, strlen($jsEntry) - 3, 3) != '.js') continue;
	$jsFiles[] = $jsEntry;
}
sort($jsFiles);
foreach ($jsFiles as $jsEntry) {
	echo "<script type=\"text/javascript\" src=\"../lib/" . $jsEntry . "\"></script>\n";
}

$cfg = new LAMCfgMain();
// check if submit button was pressed
if (isset($_POST['submit'])) {
	// check master password
	if (!$cfg->checkPassword($_POST['passwd'])) {
		$error = _("Master password is wrong!");
	}
	// add new profile
	elseif ($_POST['action'] == "add") {
		if (preg_match("/^[a-z0-9_-]+$/i", $_POST['addprofile']) && !in_array($_POST['addprofile'], getConfigProfiles())) {
			// check profile password
			if ($_POST['addpassword'] && $_POST['addpassword2'] && ($_POST['addpassword'] == $_POST['addpassword2'])) {
				// check if lam.conf_sample exists
				if (!is_file("../../config/lam.conf_sample")) {
					$error = _("The file config/lam.conf_sample was not found. Please restore it.");				
				}
				else {
					// create new profile file
					@copy("../../config/lam.conf_sample", "../../config/" . $_POST['addprofile'] . ".conf");
					@chmod ("../../config/" . $_POST['addprofile'] . ".conf", 0600);
					$file = is_file("../../config/" . $_POST['addprofile'] . ".conf");
					if ($file) {
						// load as config and write new password
						$conf = new LAMConfig($_POST['addprofile']);
						$conf->set_Passwd($_POST['addpassword']);
						$conf->save();
						$msg = _("Created new profile.");
					}
					else {
						$error = _("Unable to create new profile!");
					}
				}
			}
			else $error = _("Profile passwords are different or empty!");
		}
		else $error = _("Profile name is invalid!");
	}
	// rename profile
	elseif ($_POST['action'] == "rename") {
		if (preg_match("/^[a-z0-9_-]+$/i", $_POST['renfilename']) && !in_array($_POST['renfilename'], getConfigProfiles())) {
			if (rename("../../config/" . $_POST['oldfilename'] . ".conf",
				"../../config/" . $_POST['renfilename'] . ".conf")) {
				$msg = _("Renamed profile.");
			}
			else $error = _("Could not rename file!");
		}
		else $error = _("Profile name is invalid!");
	}
	// delete profile
	elseif ($_POST['action'] == "delete") {
		if (preg_match("/^[a-z0-9_-]+$/i", $_POST['delfilename']) && @unlink("../../config/" . $_POST['delfilename'] . ".conf")) {
			$msg = _("Profile deleted.");
		}
		else $error = _("Unable to delete profile!");
	}
	// set new profile password
	elseif ($_POST['action'] == "setpass") {
		if ($_POST['setpassword'] && $_POST['setpassword2'] && ($_POST['setpassword'] == $_POST['setpassword2'])) {
			$config = new LAMConfig($_POST['setprofile']);
			$config->set_Passwd($_POST['setpassword']);
			$config->save();
			$config = null;
			$msg = _("New password set successfully.");
		}
		else $error = _("Profile passwords are different or empty!");
	}
	// set default profile
	elseif ($_POST['action'] == "setdefault") {
		$configMain = new LAMCfgMain();
		$configMain->default = $_POST['defaultfilename'];
		$configMain->save();
		$configMain = null;
		$msg = _("New default profile set successfully.");
	}
	// print messages
	if (isset($error) || isset($msg)) {
		if (isset($error)) {
			StatusMessage("ERROR", $error);
		}
		if (isset($msg)) {
			StatusMessage("INFO", $msg);
		}
	}
	else exit;
}


// check if config.cfg is valid
if (!isset($cfg->default)) {
	StatusMessage("ERROR", _("Please set up your master configuration file (config/config.cfg) first!"), "");
	echo "</body>\n</html>\n";
	die();
}

?>

		<br>
		<!-- form for adding/renaming/deleting profiles -->
		<form action="profmanage.php" method="post">
		<table>
		<tr><td>
		<fieldset>
			<legend><b> <?php echo _("Profile management"); ?> </b></legend>
			<br>
			<table cellspacing=0 border=0>

				<!-- add profile -->
				<tr>
					<td>
						<input type="radio" name="action" value="add" checked>
					</td>
					<td>
						<b>
							<?php echo _("Add profile") . ":"; ?>
						</b>
					</td>
					<td align="right">
						<?php echo _("Profile name") . ":"; ?>
						<input type="text" name="addprofile">
					</td>
					<td>&nbsp;
					<?PHP
						printHelpLink(getHelp('', '230'), '230');
					?>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right">
						<?php echo _("Profile password") . ":"; ?>
						<input type="password" name="addpassword">
					</td>
					<td></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right">
						<?php echo _("Reenter profile password") . ":"; ?>
						<input type="password" name="addpassword2">
					</td>
					<td></td>
				</tr>

				<tr>
					<td colspan=4>&nbsp;</td>
				</tr>

				<!-- rename profile -->
				<tr>
					<td>
						<input type="radio" name="action" value="rename">
					</td>
					<td>
						<select size=1 name="oldfilename">
						<?php
							$files = getConfigProfiles();
							for ($i = 0; $i < sizeof($files); $i++) echo ("<option>" . $files[$i] . "</option>\n");
						?>
						</select>
						<b>
							<?php echo _("Rename profile"); ?>
						</b>
					</td>
					<td align="right">
						<?php echo _("Profile name") . ":"; ?>
						<input type="text" name="renfilename">
					</td>
					<td>&nbsp;
					<?PHP
						printHelpLink(getHelp('', '231'), '231');
					?>
					</td>
				</tr>

				<tr>
					<td colspan=4>&nbsp;</td>
				</tr>

				<!-- delete profile -->
				<tr>
					<td>
						<input type="radio" name="action" value="delete">
					</td>
					<td colspan=2>
						<select size=1 name="delfilename">
						<?php
							$files = getConfigProfiles();
							for ($i = 0; $i < sizeof($files); $i++) echo ("<option>" . $files[$i] . "</option>\n");
						?>
						</select>
						<b>
							<?php echo _("Delete profile"); ?>
						</b>
					</td>
					<td>&nbsp;
					<?PHP
						printHelpLink(getHelp('', '232'), '232');
					?>
					</td>
				</tr>

				<tr>
					<td colspan=4>&nbsp;</td>
				</tr>

				<!-- set profile password -->
				<tr>
					<td>
						<input type="radio" name="action" value="setpass">
					</td>
					<td>
						<select size=1 name="setprofile">
						<?php
							$files = getConfigProfiles();
							for ($i = 0; $i < sizeof($files); $i++) echo ("<option>" . $files[$i] . "</option>\n");
						?>
						</select>
						<b>
							<?php echo _("Set profile password"); ?>
						</b>
					</td>
					<td align="right">
						<?php echo _("Profile password") . ":"; ?>
						<input type="password" name="setpassword">
					</td>
					<td>&nbsp;
					<?PHP
						printHelpLink(getHelp('', '233'), '233');
					?>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right">
						<?php echo _("Reenter profile password") . ":"; ?>
						<input type="password" name="setpassword2">
					</td>
					<td>&nbsp;</td>
				</tr>

				<tr>
					<td colspan=4>&nbsp;</td>
				</tr>

				<!-- change default profile -->
				<tr>
					<td>
						<input type="radio" name="action" value="setdefault">
					</td>
					<td>
						<select size=1 name="defaultfilename">
						<?php
							$files = getConfigProfiles();
							$conf = new LAMCfgMain();
							$defaultprofile = $conf->default;
							for ($i = 0; $i < sizeof($files); $i++) {
								if ($files[$i] == $defaultprofile) echo ("<option selected>" . $files[$i] . "</option>\n");
								else echo ("<option>" . $files[$i] . "</option>\n");
							}
						?>
						</select>
						<b>
							<?php echo _("Change default profile"); ?>
						</b>
					</td>
					<td>&nbsp;</td>
					<td>&nbsp;
					<?PHP
						printHelpLink(getHelp('', '234'), '234');
					?>
					</td>
				</tr>

			</table>
			</fieldset>
			</td></tr>
			</table>
			<p>&nbsp;</p>

			<!-- password field and submit button -->
			<b>
				<?php echo _("Master password"); ?>:
			</b>
			&nbsp;
			<input type="password" name="passwd">
			&nbsp;
			<button id="submitButton" name="submit" class="smallPadding"><?php echo _("Ok"); ?></button>
			&nbsp;
			<?PHP
				printHelpLink(getHelp('', '236'), '236');
			?>
			<script type="text/javascript" language="javascript">
			jQuery(document).ready(function() {
				jQuery('#submitButton').button();
			});
			</script>

		</form>
		<p><br></p>

	</body>
</html>


<?php require('../../application/configuration/prepend.inc.php'); ?>
<?php require('../includes/header.inc.php'); ?>

	<div class="instructions">
		<div class="instruction_title">ExpandAsArray: Multiple Related Tables in One Swift Query</div>		
		You've certainly had to deal with some sort of hierarchical data in your
		database. Let's say you have a set of <b>Persons</b>; each person can be
		a manager for a <b>Project</b>. Each <b>Project</b> has one or more milestones.
		Oh, wait! And each <b>Person</b> has one or more <b>Addresses</b>.<br /><br />
		
		So, if you were to look at the schema subsection visually, it would look like this:<br />
		<img src="expandasarray_schema_diagram.png" /><br /><br />
		
		What if you need to display BOTH the project information, and the address
		information, for each of the people in your database? A standard approach
		would be to issue two queries - one for addresses, another one for projects;
		you'd then need to somehow merge the two arrays to be able to output the
		address and the projects of the same person at once. Pain..<br /><br />
		
		Well, no more pain. <b>ExpandAsArray</b> to your rescue. Note that this
		is a somewhat advanced topic - so if you're not comfortable with the
		concepts of <a href="../more_codegen/early_bind.php">QCubed Early Binding</a> and
		<a href="qqclause.php">QQ::Clauses</a>, read up on those first. <br /><br />
		
		We'll issue one mega-powerful query that will allow you to get BOTH the
		<b>Address</b> and the <b>Project</b> data (with the related info on
		the <b>Milestones</b> for each project) in one powerful sweep. Moreover,
        this will only execute a single query against your database backend.
        Essentially, what will happen here is you'll get an object and ALL
        types of related objects for it - something that SQL isn't really meant
        to do. Object-oriented databases would be an exit, but we love our
        relational systems too much, don't we? <br /><br />
		
		Here's that magical expression:<br />
		<div style="padding-left: 50px"><code>
		$arrPersons = Person::LoadAll(QQ::Clause(<br />
		&nbsp;&nbsp;&nbsp;QQ::ExpandAsArray(QQN::Person()->Address),<br />
		&nbsp;&nbsp;&nbsp;QQ::ExpandAsArray(QQN::Person()->ProjectAsManager),<br />
		&nbsp;&nbsp;&nbsp;QQ::ExpandAsArray(QQN::Person()->ProjectAsManager->Milestone)<br />
		));
		</code></div><br />
		
		The resulting <b>$arrPersons</b> will be an array of objects of type
		<b>Person</b>. Each of those objects will have member variables called
		<b>_AddressArray</b> (array of <b>Address</b> objects) and <b>_ProjectAsManagerArray</b>
		(array of <b>Project</b> objects). Each of the <b>Project</b> objects will also
		have a member variable <b>_MilestoneArray</b>, containing an array of <b>Milestone</b>
		objects. It's then trivial to iterate through the <b>$arrPersons</b> to output all
		of that data - all the <b>Project</b> and <b>Address</b> is now neatly
		organized under each <b>Person</b>.<br /><br />
		
		NOTE: Be careful around the number of items in each of the tables that will
		be returned by the query that you execute. In the example above, the total
		number of rows returned from SQL in that one query is equal to:<br />
		<center><b>(Num of Persons) * (Num of Projects) * (Num of Milestones) *
		(Num of Addresses)</b></center><br />
		You can see how it can get out of hand quickly - and the performance gains
		you get out of issuing a single query can become a detriment instead, because
		of the amount of data that gets transfered from your database server to PHP.
		Thus, this approach only makes sense if you don't expect to have hundreds of
		items in each of the tables you're extracting the data from. Be sure to look
		at the SQL statement generated by QQuery, and try running it yourself, keeping
		the number of results in mind. 
	</div>
	<h3>Projects and Addresses for each Person</h3>    

<?php    
QApplication::$Database[1]->EnableProfiling();
	
$people = Person::LoadAll(
	QQ::Clause(
		QQ::ExpandAsArray(QQN::Person()->Address),
		QQ::ExpandAsArray(QQN::Person()->ProjectAsManager),
		QQ::ExpandAsArray(QQN::Person()->ProjectAsManager->Milestone)
	)
);

foreach ($people as $person) {
	echo "<b>" . $person->FirstName . " " . $person->LastName . "</b><br />";
	echo "Addresses: ";
	if (sizeof($person->_AddressArray) == 0) {
		echo "none";
	} else {
		foreach ($person->_AddressArray as $address) {
			echo $address->Street . "; ";
		}
	}
	echo "<br />";

	echo "Projects where this person is a project manager: ";
	if (sizeof($person->_ProjectAsManagerArray) == 0) {
		echo "none<br />";
	} else {
		echo "<br />";
		foreach($person->_ProjectAsManagerArray as $project) {
			echo $project->Name . " (milestones: ";

			if (sizeof($project->_MilestoneArray) == 0) {
				echo "none";
			} else {
				foreach ($project->_MilestoneArray as $milestone) {
					echo $milestone->Name . "; ";
				}
			}
			echo ")<br />";
		}
	}
	echo "<br />";
}

QApplication::$Database[1]->OutputProfiling();
?>
	
<?php require('../includes/footer.inc.php'); ?>
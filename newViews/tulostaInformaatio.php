<div>
    <p><?php echo "Artikkeleiden määrä: " . $taulunPituus ?></p>
    <p>Taulun rakenne:</p>
    <table>
        <tr>
            <th>Nimi</th>
            <th>Tyyppi</th>
            <th>Voiko olla tyhjä</th>
            <th>Avain tyyppi</th>
            <th>Oletusarvo</th>
            <th>Extra</th>
        </tr>
    <?php 
        foreach($taulunInformaatio as $rivi) {
            echo '<tr>';
            echo '<td>' . $rivi['Field'] . '</td>';
            echo '<td>' . $rivi['Type'] . '</td>';
            echo '<td>' . $rivi['Null'] . '</td>';
            echo '<td>' . $rivi['Key'] . '</td>';
            echo '<td>' . $rivi['Default'] . '</td>';
            echo '<td>' . $rivi['Extra'] . '</td>';
            echo '</tr>';
        }
        /*foreach($taulunInformaatio as $rivi) {
        echo '<p>' . $rivi['Field'] . ' ' . $rivi['Type'] . ' ' . ($rivi['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . ' ' . $rivi['Key'] . ' ' . $rivi['Default'] . ' ' . $rivi['Extra'] . '</p>';
    } */?>
    </table>
    <p><?php echo "Taulun merkitsö: " . $taulunMerkisto ?> </p>
</div>
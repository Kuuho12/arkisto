<div id="kirjautuminenlomake">
    <form action="kirjautuminen.php" method="post">
        <h2>Kirjaudu sisään</h2>
        <label for="username">Käyttäjätunnus:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Salasana:</label>
        <input type="password" id="password" name="password" required><br><br>
        <input type="submit" value="Kirjaudu">
        <p><?php echo $error ?? ''; ?></p>
    </form>
</div>
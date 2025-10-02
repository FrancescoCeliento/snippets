import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

/** Una classe Java per gestire un database SQLite
  *
  */
public class DatabaseSQLiteManager {
    private String url;
    private Connection connection;

    public DatabaseSQLiteManager(String dbUrl) {
        this.url = dbUrl;
    }

    // Metodo per connettersi al database
    // Nota: SQLite crea automaticamente il file del database se non esiste già, quindi non è necessario alcun codice aggiuntivo per gestire la creazione del file
    private Connection connect() throws SQLException {
        if (connection == null || connection.isClosed()) {
            connection = DriverManager.getConnection(url);
            connection.setAutoCommit(false); // Disabilita il commit automatico
        }
        return connection;
    }

    // Metodo per eseguire una query di selezione con parametri
    public ResultSet selectData(String sql, Object... params) {
        ResultSet rs = null;
        try {
            PreparedStatement pstmt = connect().prepareStatement(sql);
            setParameters(pstmt, params); // Imposta i parametri
            rs = pstmt.executeQuery();
        } catch (SQLException e) {
            e.printStackTrace();
            return null;
        }
        // Ricorda di ciclare su rs.next() (es while (rs != null && rs.next()) { TODO }) nel chiamante
        /* Per leggere campi int rs.getInt('campo'), per leggere campi string rs.getString('campo')
        */
        return rs;
    }

    // Metodo per scrivere (inserire, aggiornare o cancellare) dati nel database
    public boolean updateData(String query, Object... params) {
        try (PreparedStatement pstmt = connect().prepareStatement(query)) {
            setParameters(pstmt, params);
            pstmt.executeUpdate();
            return true;
        } catch (SQLException e) {
            e.printStackTrace();
            return false;
        }
    }

    // Metodo per eseguire query DDL (CREATE, ALTER, DROP, ecc... )
    public void executeQuery(String sql) {
        try (PreparedStatement pstmt = connect().prepareStatement(sql)) {
            pstmt.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // Metodo per impostare i parametri nel PreparedStatement
    private void setParameters(PreparedStatement pstmt, Object... params) throws SQLException {
        for (int i = 0; i < params.length; i++) {
            pstmt.setObject(i + 1, params[i]);
        }
    }

    // Metodo per ottimizzare il database
    public void optimizeDatabase() {
        String query = "VACUUM"; // Comando per ottimizzare il database
        try (PreparedStatement pstmt = connect().prepareStatement(query)) {
            pstmt.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // Metodo per eseguire il commit delle modifiche
    public void commitTransaction() {
        try {
            if (connection != null && !connection.isClosed()) {
                connection.commit();
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // Metodo per eseguire il rollback delle modifiche
    public void rollbackTransaction() {
        try {
            if (connection != null && !connection.isClosed()) {
                connection.rollback();
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // Metodo per chiudere la connessione al database
    public void closeConnection() {
        try {
            if (connection != null && !connection.isClosed()) {
                connection.close();
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }
}

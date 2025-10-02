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
    private Connection connect() throws SQLException {
        if (connection == null || connection.isClosed()) {
            connection = DriverManager.getConnection(url);
            connection.setAutoCommit(false); // Disabilita il commit automatico
        }
        return connection;
    }

    // Metodo per scrivere (inserire) dati nel database
    public void insertData(String query, Object... params) {
        try (PreparedStatement pstmt = connect().prepareStatement(query)) {
            setParameters(pstmt, params);
            pstmt.executeUpdate();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // Metodo per aggiornare dati nel database
    public void updateData(String query, Object... params) {
        insertData(query, params); // Riutilizziamo il metodo insertData per l'aggiornamento
    }

    // Metodo per eliminare dati dal database
    public void deleteData(String query, Object... params) {
        insertData(query, params); // Riutilizziamo il metodo insertData per l'eliminazione
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

    // Metodo per eseguire una query di selezione
    public ResultSet selectData(String query, Object... params) {
        ResultSet rs = null;
        try {
            PreparedStatement pstmt = connect().prepareStatement(query);
            setParameters(pstmt, params);
            rs = pstmt.executeQuery();
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return rs;
    }

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

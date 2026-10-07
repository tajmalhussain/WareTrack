package com.waretrack.servlet;

import com.waretrack.model.Product;
import com.waretrack.util.DBConnection;

import jakarta.servlet.ServletException;
import jakarta.servlet.annotation.WebServlet;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

import java.io.IOException;
import java.io.PrintWriter;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * WareTrack - Product Servlet for CRUD operations
 * Maps to /api/products and /products-servlet
 */
@WebServlet("/api/products")
public class ProductServlet extends HttpServlet {

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        resp.setContentType("application/json;charset=UTF-8");
        PrintWriter out = resp.getWriter();

        String category = req.getParameter("category");
        String search = req.getParameter("search");

        List<Product> list = new ArrayList<>();

        StringBuilder sql = new StringBuilder("SELECT * FROM products WHERE 1=1");
        if (category != null && !category.equalsIgnoreCase("All")) {
            sql.append(" AND category = ?");
        }
        if (search != null && !search.trim().isEmpty()) {
            sql.append(" AND (name LIKE ? OR id LIKE ? OR location LIKE ?)");
        }
        sql.append(" ORDER BY created_at DESC");

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql.toString())) {

            int paramIndex = 1;
            if (category != null && !category.equalsIgnoreCase("All")) {
                ps.setString(paramIndex++, category);
            }
            if (search != null && !search.trim().isEmpty()) {
                String term = "%" + search.trim() + "%";
                ps.setString(paramIndex++, term);
                ps.setString(paramIndex++, term);
                ps.setString(paramIndex++, term);
            }

            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Product p = new Product(
                        rs.getString("id"),
                        rs.getString("name"),
                        rs.getString("category"),
                        rs.getInt("quantity"),
                        rs.getInt("min_stock"),
                        rs.getDouble("price"),
                        rs.getString("location"),
                        rs.getString("icon")
                    );
                    list.add(p);
                }
            }

            // Simple JSON serialization without external libraries
            StringBuilder json = new StringBuilder("[");
            for (int i = 0; i < list.size(); i++) {
                Product p = list.get(i);
                json.append(String.format(
                    "{\"id\":\"%s\",\"name\":\"%s\",\"category\":\"%s\",\"quantity\":%d,\"min_stock\":%d,\"price\":%.2f,\"location\":\"%s\",\"icon\":\"%s\"}",
                    escapeJson(p.getId()), escapeJson(p.getName()), escapeJson(p.getCategory()),
                    p.getQuantity(), p.getMinStock(), p.getPrice(), escapeJson(p.getLocation()), escapeJson(p.getIcon())
                ));
                if (i < list.size() - 1) json.append(",");
            }
            json.append("]");

            out.print(json.toString());

        } catch (SQLException e) {
            resp.setStatus(HttpServletResponse.SC_INTERNAL_SERVER_ERROR);
            out.print("{\"error\":\"" + escapeJson(e.getMessage()) + "\"}");
        }
    }

    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        resp.setContentType("application/json;charset=UTF-8");
        PrintWriter out = resp.getWriter();

        String id = req.getParameter("id");
        String name = req.getParameter("name");
        String category = req.getParameter("category");
        int quantity = parseInt(req.getParameter("quantity"), 0);
        int minStock = parseInt(req.getParameter("min_stock"), 10);
        double price = parseDouble(req.getParameter("price"), 0.0);
        String location = req.getParameter("location");
        String icon = req.getParameter("icon");

        if (id == null || id.trim().isEmpty()) {
            id = "PRD-" + (System.currentTimeMillis() % 10000);
        }
        if (location == null) location = "General Storage";
        if (icon == null) icon = "📦";

        String sql = "INSERT INTO products (id, name, category, quantity, min_stock, price, location, icon) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        try (Connection conn = DBConnection.getConnection();
             PreparedStatement ps = conn.prepareStatement(sql)) {

            ps.setString(1, id);
            ps.setString(2, name);
            ps.setString(3, category);
            ps.setInt(4, quantity);
            ps.setInt(5, minStock);
            ps.setDouble(6, price);
            ps.setString(7, location);
            ps.setString(8, icon);

            ps.executeUpdate();
            resp.setStatus(HttpServletResponse.SC_CREATED);
            out.print("{\"success\":true,\"message\":\"Product registered successfully\",\"id\":\"" + id + "\"}");

        } catch (SQLException e) {
            resp.setStatus(HttpServletResponse.SC_INTERNAL_SERVER_ERROR);
            out.print("{\"success\":false,\"error\":\"" + escapeJson(e.getMessage()) + "\"}");
        }
    }

    private int parseInt(String val, int def) {
        try { return Integer.parseInt(val); } catch (Exception e) { return def; }
    }

    private double parseDouble(String val, double def) {
        try { return Double.parseDouble(val); } catch (Exception e) { return def; }
    }

    private String escapeJson(String s) {
        if (s == null) return "";
        return s.replace("\\", "\\\\").replace("\"", "\\\"").replace("\n", "\\n").replace("\r", "\\r");
    }
}

package com.waretrack.model;

/**
 * WareTrack - Product Entity Model
 */
public class Product {
    private String id;
    private String name;
    private String category;
    private int quantity;
    private int minStock;
    private double price;
    private String location;
    private String icon;

    public Product() {}

    public Product(String id, String name, String category, int quantity, int minStock, double price, String location, String icon) {
        this.id = id;
        this.name = name;
        this.category = category;
        this.quantity = quantity;
        this.minStock = minStock;
        this.price = price;
        this.location = location;
        this.icon = icon;
    }

    public String getId() { return id; }
    public void setId(String id) { this.id = id; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public String getCategory() { return category; }
    public void setCategory(String category) { this.category = category; }

    public int getQuantity() { return quantity; }
    public void setQuantity(int quantity) { this.quantity = quantity; }

    public int getMinStock() { return minStock; }
    public void setMinStock(int minStock) { this.minStock = minStock; }

    public double getPrice() { return price; }
    public void setPrice(double price) { this.price = price; }

    public String getLocation() { return location; }
    public void setLocation(String location) { this.location = location; }

    public String getIcon() { return icon; }
    public void setIcon(String icon) { this.icon = icon; }
}

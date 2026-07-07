# Future Product Import CSV Image Format

Product Import is intentionally not implemented in Milestone 4.9A.

When Product Import is approved, CSV columns should keep brand and image fields in this order:

```csv
product_name,brand_name,category,subcategory,sub_subcategory,variant_name,sku,mrp,selling_price,product_image,variant_image
```

`sub_subcategory` is optional. It may be blank when the product belongs directly under a subcategory.

Example with a blank optional `sub_subcategory` value:

```csv
product_name,brand_name,category,subcategory,sub_subcategory,variant_name,sku,mrp,selling_price,product_image,variant_image
Fortune Sunflower Oil,Fortune,Foodgrains,Edible Oils,,1L,FORTUNE-OIL-1L,180,165,fortune-sunflower-oil.webp,fortune-sunflower-oil-1l.webp
```

Image fields should reference safe relative filenames or paths prepared for import mapping. Runtime uploads continue to use `public/uploads/` and store relative paths such as `uploads/products/example.webp`.

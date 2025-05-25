<?php

namespace App\Livewire;

use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Modules\Product\Entities\Product;
use App\Models\ProductBatch;

class ProductCart extends Component
{
    public $listeners = ['productSelected', 'discountModalRefresh'];

    public $cart_instance;
    public $global_discount;
    public $shipping;
    public $quantity;
    public $check_quantity;
    public $discount_type;
    public $item_discount;
    public $unit_price;
    public $data;

    private $product;

    public function mount($cartInstance, $data = null) {
        $this->cart_instance = $cartInstance;

        if ($data) {
            $this->data = $data;
            $this->global_discount = $data->discount_percentage;
            $this->shipping = $data->shipping_amount;
            $this->updatedGlobalDiscount();

            $cart_items = Cart::instance($this->cart_instance)->content();
            foreach ($cart_items as $cart_item) {
                $this->check_quantity[$cart_item->id] = [$cart_item->options->stock];
                $this->quantity[$cart_item->id] = $cart_item->qty;
                $this->unit_price[$cart_item->id] = $cart_item->options->unit_price;
                $this->discount_type[$cart_item->id] = $cart_item->options->product_discount_type;
                if ($cart_item->options->product_discount_type == 'fixed') {
                    $this->item_discount[$cart_item->id] = $cart_item->options->product_discount;
                } elseif ($cart_item->options->product_discount_type == 'percentage') {
                    $this->item_discount[$cart_item->id] = round(100 * ($cart_item->options->product_discount / $cart_item->price));
                }
            }
        } else {
            $this->global_discount = 0;
            $this->shipping = 0.00;
            $this->check_quantity = [];
            $this->quantity = [];
            $this->unit_price = [];
            $this->discount_type = [];
            $this->item_discount = [];
        }
    }

    public function render() {
        $cart_items = Cart::instance($this->cart_instance)->content();
        return view('livewire.product-cart', [
            'cart_items' => $cart_items
        ]);
    }

    public function productSelected($product) {
        $cart = Cart::instance($this->cart_instance);

        $exists = $cart->search(function ($cartItem, $rowId) use ($product) {
            return $cartItem->id == $product['id'];
        });

        if ($exists->isNotEmpty()) {
            session()->flash('message', 'Product exists in the cart!');
            return;
        }

        $branch_id = session('branch_id');
        if (!$branch_id) {
            session()->flash('message', 'Branch belum dipilih.');
            return;
        }

        $batch = ProductBatch::where('product_id', $product['id'])
            ->where('branch_id', $branch_id)
            ->where('qty', '>', 0)
            ->orderBy('exp_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->first();

        if (!$batch) {
            session()->flash('message', 'Stok produk tidak tersedia.');
            return;
        }

        $unit_price = $batch->unit_price;
        $stock_qty = $batch->qty;

        $cart->add([
            'id'      => $product['id'],
            'name'    => $product['product_name'],
            'qty'     => 1,
            'price'   => $unit_price,
            'weight'  => 1,
            'options' => [
                'product_discount'      => 0.00,
                'product_discount_type' => 'fixed',
                'sub_total'             => $unit_price,
                'code'                  => $product['product_code'],
                'stock'                 => $stock_qty,
                'unit'                  => $product['product_unit'],
                'unit_price'            => $unit_price,
                'batch_id'              => $batch->id,
                'exp_date'              => $batch->exp_date,
            ]
        ]);

        $productId = $product['id'];
        $this->check_quantity[$productId] = $stock_qty;
        $this->quantity[$productId] = 1;
        $this->discount_type[$productId] = 'fixed';
        $this->item_discount[$productId] = 0;
        $this->unit_price[$productId] = $unit_price;
    }

    public function removeItem($row_id) {
        Cart::instance($this->cart_instance)->remove($row_id);
    }

    public function updatedGlobalDiscount() {
        Cart::instance($this->cart_instance)->setGlobalDiscount((int) $this->global_discount);
    }

    public function updateQuantity($row_id, $product_id) {
        if (
            in_array($this->cart_instance, ['sale', 'purchase_return']) &&
            isset($this->check_quantity[$product_id], $this->quantity[$product_id]) &&
            $this->check_quantity[$product_id] < $this->quantity[$product_id]
        ) {
            session()->flash('message', 'The requested quantity is not available in stock.');
            return;
        }

        $qty = $this->quantity[$product_id] ?? 1;
        Cart::instance($this->cart_instance)->update($row_id, $qty);

        $cart_item = Cart::instance($this->cart_instance)->get($row_id);

        Cart::instance($this->cart_instance)->update($row_id, [
            'options' => [
                'sub_total'             => $cart_item->price * $cart_item->qty,
                'code'                  => $cart_item->options->code ?? '',
                'stock'                 => $cart_item->options->stock ?? 0,
                'unit'                  => $cart_item->options->unit ?? '',
                'unit_price'            => $cart_item->options->unit_price ?? 0,
                'product_discount'      => $cart_item->options->product_discount ?? 0,
                'product_discount_type' => $cart_item->options->product_discount_type ?? 'fixed',
            ]
        ]);
    }

    public function updatedDiscountType($value, $name) {
        $this->item_discount[$name] = 0;
    }

    public function discountModalRefresh($product_id, $row_id) {
        $this->updateQuantity($row_id, $product_id);
    }

    public function setProductDiscount($row_id, $product_id) {
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);

        if ($this->discount_type[$product_id] == 'fixed') {
            Cart::instance($this->cart_instance)->update($row_id, [
                'price' => ($cart_item->price + $cart_item->options->product_discount) - $this->item_discount[$product_id]
            ]);

            $discount_amount = $this->item_discount[$product_id];
            $this->updateCartOptions($row_id, $product_id, $cart_item, $discount_amount);

        } elseif ($this->discount_type[$product_id] == 'percentage') {
            $discount_amount = ($cart_item->price + $cart_item->options->product_discount) * ($this->item_discount[$product_id] / 100);

            Cart::instance($this->cart_instance)->update($row_id, [
                'price' => ($cart_item->price + $cart_item->options->product_discount) - $discount_amount
            ]);

            $this->updateCartOptions($row_id, $product_id, $cart_item, $discount_amount);
        }

        session()->flash('discount_message' . $product_id, 'Discount added to the product!');
    }

    public function updatePrice($row_id, $product_id) {
        $product = Product::findOrFail($product_id);
        $cart_item = Cart::instance($this->cart_instance)->get($row_id);

        Cart::instance($this->cart_instance)->update($row_id, ['price' => $this->unit_price[$product['id']]]);

        Cart::instance($this->cart_instance)->update($row_id, [
            'options' => [
                'sub_total'             => $this->calculate($product, $this->unit_price[$product['id']])['sub_total'],
                'code'                  => $cart_item->options->code,
                'stock'                 => $cart_item->options->stock,
                'unit'                  => $cart_item->options->unit,
                'unit_price'            => $this->calculate($product, $this->unit_price[$product['id']])['unit_price'],
                'product_discount'      => $cart_item->options->product_discount,
                'product_discount_type' => $cart_item->options->product_discount_type,
            ]
        ]);
    }

    public function calculate($product, $new_price = null) {
        $productId = is_array($product) ? $product['id'] : $product->id;
        $product_price = $new_price ?? (
            ($this->cart_instance === 'purchase' || $this->cart_instance === 'purchase_return') 
                ? $product['product_cost'] ?? 0 
                : $product['product_price'] ?? 0
        );

        if (
            isset($product['min_quantity_for_wholesale'], $product['wholesale_discount_percentage'], $this->quantity[$productId]) &&
            $this->quantity[$productId] >= $product['min_quantity_for_wholesale']
        ) {
            $discount = $product['wholesale_discount_percentage'] / 100;
            $product_price *= (1 - $discount);
        }

        return [
            'price'       => $product_price,
            'unit_price'  => $product_price,
            'sub_total'   => $product_price,
        ];
    }

    public function updateCartOptions($row_id, $product_id, $cart_item, $discount_amount) {
        Cart::instance($this->cart_instance)->update($row_id, ['options' => [
            'sub_total'             => $cart_item->price * $cart_item->qty,
            'code'                  => $cart_item->options->code,
            'stock'                 => $cart_item->options->stock,
            'unit'                  => $cart_item->options->unit,
            'unit_price'            => $cart_item->options->unit_price,
            'product_discount'      => $discount_amount,
            'product_discount_type' => $this->discount_type[$product_id],
        ]]);
    }
}

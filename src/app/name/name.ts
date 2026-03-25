import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-name',
  templateUrl: './name.html',
  styleUrl: './name.css',
  standalone: false
})
export class Name {
  @Input() productData = {
    name: 'Bed Side Table',
    description: 'A beautiful side table that will perfectly fit your lovely bed room. With space for your books, lamps and electronic devices.'
  };

  // --- CẤU TRÚC DỮ LIỆU ĐÃ CẬP NHẬT (3 MÀU & 3 SIZE) ---
  productVariants = [
    {
      colorName: 'Black',
      colorCode: '#1a1a1a',
      price: 15000,
      oldPrice: 25000,
      images: [
        'https://i.pinimg.com/1200x/be/85/b9/be85b9852b2633e58087b3df54eda5b2.jpg',
        'https://i.pinimg.com/1200x/be/85/b9/be85b9852b2633e58087b3df54eda5b2.jpg',
        'https://i.pinimg.com/1200x/be/85/b9/be85b9852b2633e58087b3df54eda5b2.jpg',
        'https://i.pinimg.com/1200x/be/85/b9/be85b9852b2633e58087b3df54eda5b2.jpg',
        'https://i.pinimg.com/1200x/be/85/b9/be85b9852b2633e58087b3df54eda5b2.jpg'
      ],
      sizes: [
        { size: '42*40', priceAdjustment: 0 },
        { size: '40*40', priceAdjustment: -500 },
        { size: '35*49', priceAdjustment: -1000 }
      ]
    },
    {
      colorName: 'Red',
      colorCode: '#d93025',
      price: 16500,
      oldPrice: 26500,
      images: [
        'https://i.pinimg.com/1200x/12/c8/1d/12c81d9f23f7d0c5a8fb3abb82e197e4.jpg',
        'https://i.pinimg.com/1200x/12/c8/1d/12c81d9f23f7d0c5a8fb3abb82e197e4.jpg',
        'https://i.pinimg.com/1200x/12/c8/1d/12c81d9f23f7d0c5a8fb3abb82e197e4.jpg',
        'https://i.pinimg.com/1200x/12/c8/1d/12c81d9f23f7d0c5a8fb3abb82e197e4.jpg',
        'https://i.pinimg.com/1200x/12/c8/1d/12c81d9f23f7d0c5a8fb3abb82e197e4.jpg'
      ],
      sizes: [
        { size: '42*40', priceAdjustment: 0 },
        { size: '40*40', priceAdjustment: -500 },
        { size: '35*49', priceAdjustment: -1000 }
      ]
    },
    {
      colorName: 'Orange',
      colorCode: '#f1a843',
      price: 15500,
      oldPrice: 25500,
      images: [
        'https://i.pinimg.com/1200x/10/bb/e3/10bbe375ded49332140e9ad8f18dfea8.jpg',
        'https://i.pinimg.com/1200x/10/bb/e3/10bbe375ded49332140e9ad8f18dfea8.jpg',
        'https://i.pinimg.com/1200x/10/bb/e3/10bbe375ded49332140e9ad8f18dfea8.jpg',
        'https://i.pinimg.com/1200x/10/bb/e3/10bbe375ded49332140e9ad8f18dfea8.jpg',
        'https://i.pinimg.com/1200x/10/bb/e3/10bbe375ded49332140e9ad8f18dfea8.jpg'
      ],
      sizes: [
        { size: '42*40', priceAdjustment: 0 },
        { size: '40*40', priceAdjustment: -500 },
        { size: '35*49', priceAdjustment: -1000 }
      ]
    }
  ];

  // Trạng thái khởi tạo
  selectedVariant = this.productVariants[0]; 
  selectedSizeObj = this.selectedVariant.sizes[0]; 
  quantity: number = 1;
  currentIndex: number = 1;

  get currentPrice() {
    return this.selectedVariant.price + this.selectedSizeObj.priceAdjustment;
  }

  onSlide(event: any) {
    this.currentIndex = event.to + 1;
  }

  updateQuantity(val: number) {
    if (this.quantity + val >= 1) {
      this.quantity += val;
    }
  }

  selectColor(variant: any) {
    this.selectedVariant = variant;
    // Tìm size tương ứng ở variant mới để giữ lựa chọn của người dùng nếu có thể
    const matchingSize = variant.sizes.find((s: any) => s.size === this.selectedSizeObj.size);
    this.selectedSizeObj = matchingSize || variant.sizes[0];
    this.currentIndex = 1;
  }

  selectSize(sizeObj: any) {
    this.selectedSizeObj = sizeObj;
  }

  onBuyNow() {
    alert(`Đã thêm vào giỏ hàng!\nMàu: ${this.selectedVariant.colorName}\nSize: ${this.selectedSizeObj.size}\nSố lượng: ${this.quantity}`);
  }

  onFavorite() {
    alert('Đã thêm vào yêu thích!');
  }
}
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class ReceiptImageService
{
    public function generate(Order $order): string
    {
        $storeName = Setting::get('business.store_name', 'TOKO KOPI NUSANTARA');
        $storeAddress = Setting::get('business.address', '');
        $storePhone = Setting::get('business.phone', '');
        
        $width = 800;
        $padding = 40;
        
        $itemHeight = 50;
        $itemsCount = $order->items->count();
        $itemsSectionHeight = max($itemsCount * $itemHeight, 100);
        
        $headerHeight = 120;
        $orderInfoHeight = 60;
        $totalsHeight = 120;
        $footerHeight = 60;
        $spacing = 30;
        
        $totalHeight = $headerHeight + $orderInfoHeight + $itemsSectionHeight + $totalsHeight + $footerHeight + ($spacing * 4);
        
        // Create image
        $image = imagecreatetruecolor($width, $totalHeight);
        
        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 26, 26, 26);
        $gray = imagecolorallocate($image, 102, 102, 102);
        $lightGray = imagecolorallocate($image, 229, 231, 235);
        $blue = imagecolorallocate($image, 37, 99, 235);
        $red = imagecolorallocate($image, 220, 38, 38);
        
        // White background
        imagefilledrectangle($image, 0, 0, $width, $totalHeight, $white);
        
        $currentY = $padding;
        
        // Store Name (centered)
        $this->drawText($image, $storeName, $width / 2, $currentY, $black, 32, 'center');
        $currentY += 50;
        
        // Store Address
        if ($storeAddress) {
            $this->drawText($image, $storeAddress, $width / 2, $currentY, $gray, 16, 'center');
            $currentY += 25;
        }
        
        // Store Phone
        if ($storePhone) {
            $this->drawText($image, $storePhone, $width / 2, $currentY, $gray, 16, 'center');
            $currentY += 30;
        }
        
        // Divider line
        imagefilledrectangle($image, $padding, $currentY, $width - $padding, $currentY + 2, $lightGray);
        $currentY += $spacing;
        
        // Invoice Info
        $this->drawText($image, 'Invoice: ' . $order->invoice_number, $padding, $currentY, $black, 18);
        $currentY += 30;
        
        $date = \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i');
        $this->drawText($image, $date, $padding, $currentY, $gray, 16);
        $currentY += $spacing;
        
        // Divider line
        imagefilledrectangle($image, $padding, $currentY, $width - $padding, $currentY + 2, $lightGray);
        $currentY += $spacing;
        
        // Items Header
        $this->drawText($image, 'Item', $padding, $currentY, $black, 16, 'left', true);
        $this->drawText($image, 'Qty', $width - $padding - 150, $currentY, $black, 16, 'right', true);
        $this->drawText($image, 'Total', $width - $padding, $currentY, $black, 16, 'right', true);
        $currentY += 30;
        
        // Items
        foreach ($order->items as $item) {
            $this->drawText($image, $item->product_name, $padding, $currentY, $black, 16);
            $this->drawText($image, 'x' . $item->quantity, $width - $padding - 150, $currentY, $gray, 16, 'right');
            $this->drawText($image, 'Rp ' . number_format($item->total, 0, ',', '.'), $width - $padding, $currentY, $black, 16, 'right');
            $currentY += $itemHeight;
        }
        
        // Divider line
        imagefilledrectangle($image, $padding, $currentY, $width - $padding, $currentY + 2, $lightGray);
        $currentY += $spacing;
        
        // Subtotal
        $this->drawText($image, 'Subtotal:', $width - $padding - 150, $currentY, $gray, 16, 'right');
        $this->drawText($image, 'Rp ' . number_format($order->subtotal, 0, ',', '.'), $width - $padding, $currentY, $black, 16, 'right');
        $currentY += 35;
        
        // Discount
        if ($order->discount_total > 0) {
            $this->drawText($image, 'Discount:', $width - $padding - 150, $currentY, $gray, 16, 'right');
            $this->drawText($image, '-Rp ' . number_format($order->discount_total, 0, ',', '.'), $width - $padding, $currentY, $red, 16, 'right');
            $currentY += 35;
        }
        
        // Grand Total
        $this->drawText($image, 'TOTAL:', $width - $padding - 150, $currentY, $black, 20, 'right', true);
        $this->drawText($image, 'Rp ' . number_format($order->grand_total, 0, ',', '.'), $width - $padding, $currentY, $blue, 24, 'right', true);
        $currentY += $totalsHeight;
        
        // Footer
        $this->drawText($image, 'Thank you for your purchase!', $width / 2, $currentY, $gray, 16, 'center');
        
        // Save image
        $filename = 'receipt_' . $order->invoice_number . '_' . time() . '.png';
        $path = 'receipts/' . $filename;
        
        // Save to temp file first
        $tempFile = tempnam(sys_get_temp_dir(), 'receipt_') . '.png';
        imagepng($image, $tempFile);
        
        // Put to storage
        Storage::disk('public')->put($path, file_get_contents($tempFile));
        
        // Cleanup
        unlink($tempFile);
        
        return Storage::disk('public')->url($path);
    }
    
    /**
     * Draw text on image
     */
    private function drawText($image, $text, $x, $y, $color, $size = 12, $align = 'left', $bold = false)
    {
        // Use built-in font (size 1-5) or freetype if available
        $font = __DIR__ . '/../../resources/fonts/Arial.ttf';
        
        if (function_exists('imagettftext') && file_exists($font)) {
            // FreeType text
            $bbox = imagettfbbox((float)$size, 0, $font, $text);
            $textWidth = $bbox[2] - $bbox[0];
            
            if ($align === 'center') {
                $x = $x - $textWidth / 2;
            } elseif ($align === 'right') {
                $x = $x - $textWidth;
            }
            
            imagettftext($image, (float)$size, 0, (int)$x, (int)$y + (int)$size, $color, $font, $text);
        } else {
            // Fallback to imagestring (limited sizes)
            $fontWidth = imagefontwidth(5);
            $fontHeight = imagefontheight(5);
            $textWidth = strlen($text) * $fontWidth;
            
            if ($align === 'center') {
                $x = $x - $textWidth / 2;
            } elseif ($align === 'right') {
                $x = $x - $textWidth;
            }
            
            imagestring($image, 5, (int)$x, (int)$y, $text, $color);
        }
    }
}

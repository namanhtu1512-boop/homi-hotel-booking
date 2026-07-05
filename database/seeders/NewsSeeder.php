<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $articles = [
            [
                'title'        => 'Homi Hotel khai trương khu vực hồ bơi vô cực',
                'excerpt'      => 'Khu vực hồ bơi vô cực mới với tầm nhìn toàn cảnh sông Hàn chính thức đi vào hoạt động, phục vụ miễn phí cho khách lưu trú.',
                'content'      => "Homi Hotel vừa hoàn thành và đưa vào hoạt động khu vực hồ bơi vô cực nằm trên tầng cao nhất của khách sạn, mang đến tầm nhìn toàn cảnh sông Hàn tuyệt đẹp.\n\nKhu vực hồ bơi mở cửa từ 6:00 đến 22:00 hàng ngày, miễn phí cho tất cả khách lưu trú tại khách sạn. Ngoài ra, quầy bar bên hồ bơi cũng phục vụ đồ uống và món ăn nhẹ suốt cả ngày.",
                'cover_image'  => 'https://picsum.photos/seed/homi-news-1/1200/700',
                'status'       => 'published',
                'published_at' => now()->subDays(20),
            ],
            [
                'title'        => 'Ưu đãi đặt phòng mùa hè 2026 chính thức khởi động',
                'excerpt'      => 'Chương trình giảm giá lên đến 20% cho tất cả loại phòng, áp dụng đến hết tháng 9.',
                'content'      => "Nhân dịp mùa hè sôi động, Homi Hotel triển khai chương trình ưu đãi đặc biệt dành cho khách hàng đặt phòng trực tuyến.\n\nSử dụng mã HOMISUMMER để được giảm ngay 15% tổng giá trị đơn đặt phòng. Chương trình áp dụng cho mọi loại phòng và không giới hạn số lượng đặt.",
                'cover_image'  => 'https://picsum.photos/seed/homi-news-2/1200/700',
                'status'       => 'published',
                'published_at' => now()->subDays(10),
            ],
            [
                'title'        => 'Nhà hàng Homi ra mắt thực đơn ẩm thực miền Trung',
                'excerpt'      => 'Thực đơn mới giới thiệu các món ăn đặc trưng miền Trung Việt Nam, chế biến bởi đầu bếp giàu kinh nghiệm.',
                'content'      => "Nhà hàng của Homi Hotel chính thức giới thiệu thực đơn ẩm thực miền Trung với hơn 20 món ăn đặc sắc, từ mì Quảng, bún bò Huế đến các món hải sản tươi sống.\n\nThực đơn được phục vụ hàng ngày từ 6:00 đến 22:00 tại nhà hàng chính của khách sạn.",
                'cover_image'  => 'https://picsum.photos/seed/homi-news-3/1200/700',
                'status'       => 'published',
                'published_at' => now()->subDays(5),
            ],
            [
                'title'        => 'Homi Hotel đạt chứng nhận 4 sao quốc tế',
                'excerpt'      => 'Khách sạn chính thức được công nhận đạt tiêu chuẩn 4 sao theo đánh giá quốc tế.',
                'content'      => "Sau quá trình đánh giá nghiêm ngặt, Homi Hotel đã chính thức được công nhận đạt tiêu chuẩn 4 sao quốc tế, khẳng định chất lượng dịch vụ và cơ sở vật chất của khách sạn.\n\nĐây là kết quả của nỗ lực không ngừng nghỉ trong việc nâng cao chất lượng phục vụ khách hàng suốt thời gian qua.",
                'cover_image'  => 'https://picsum.photos/seed/homi-news-4/1200/700',
                'status'       => 'published',
                'published_at' => now()->subMonth(),
            ],
            [
                'title'        => 'Kế hoạch cải tạo sảnh chính (bản nháp)',
                'excerpt'      => 'Bài viết nội bộ đang soạn thảo, chưa công bố tới khách hàng.',
                'content'      => 'Nội dung đang được hoàn thiện.',
                'cover_image'  => 'https://picsum.photos/seed/homi-news-5/1200/700',
                'status'       => 'draft',
                'published_at' => null,
            ],
        ];

        foreach ($articles as $article) {
            $article['slug'] = Str::slug($article['title']);
            News::firstOrCreate(['slug' => $article['slug']], $article);
        }
    }
}

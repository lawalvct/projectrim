<?php

namespace Database\Seeders;

use App\Models\Download;
use App\Models\Faculty;
use App\Models\Like;
use App\Models\Product;
use App\Models\ProductAuthor;
use App\Models\ProductFile;
use App\Models\ProductImage;
use App\Models\Revenue;
use App\Models\Review;
use App\Models\SellerProfile;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('12345678');
        $now = now();

        // ─── 1. USERS (20 user role + 10 seller role) ───

        $nigerianFirstNames = [
            'Chinedu', 'Amara', 'Obinna', 'Ngozi', 'Emeka',
            'Chidinma', 'Tunde', 'Funke', 'Adewale', 'Yetunde',
            'Ikenna', 'Bimpe', 'Olumide', 'Kemi', 'Chukwuemeka',
            'Aisha', 'Babatunde', 'Halima', 'Ifeanyi', 'Zainab',
            'Uche', 'Nneka', 'Damilola', 'Fatima', 'Segun',
        ];
        $nigerianLastNames = [
            'Okafor', 'Adeyemi', 'Nwachukwu', 'Balogun', 'Eze',
            'Ogundimu', 'Abubakar', 'Olawale', 'Nnamdi', 'Ibrahim',
            'Chukwu', 'Adeleke', 'Okoro', 'Adekunle', 'Uzoma',
            'Bakare', 'Obi', 'Abdullahi', 'Okeke', 'Ayodeji',
            'Nwosu', 'Oladipo', 'Idris', 'Onyeka', 'Lawal',
        ];
        $internationalNames = [
            ['James', 'Mitchell'], ['Sarah', 'Chen'], ['Mohammed', 'Al-Rashid'],
            ['Priya', 'Sharma'], ['Carlos', 'Rodriguez'],
        ];

        $allUsers = collect();

        // 20 user-role users (15 Nigerian + 5 international)
        for ($i = 0; $i < 15; $i++) {
            $first = $nigerianFirstNames[$i];
            $last = $nigerianLastNames[$i];
            $allUsers->push(User::create([
                'name' => "$first $last",
                'email' => strtolower($first) . '.' . strtolower($last) . '@example.com',
                'password' => $password,
                'role' => 'user',
                'email_verified_at' => $now->copy()->subDays(rand(1, 90)),
                'created_at' => $now->copy()->subDays(rand(30, 180)),
            ]));
        }
        foreach ($internationalNames as $intl) {
            $allUsers->push(User::create([
                'name' => "{$intl[0]} {$intl[1]}",
                'email' => strtolower($intl[0]) . '.' . strtolower($intl[1]) . '@example.com',
                'password' => $password,
                'role' => 'user',
                'email_verified_at' => $now->copy()->subDays(rand(1, 90)),
                'created_at' => $now->copy()->subDays(rand(30, 180)),
            ]));
        }

        $users = $allUsers; // 20 users

        // 10 seller-role users (Nigerian names)
        $sellerNames = [
            ['Ogechi', 'Nwoye'], ['Tobiloba', 'Adegoke'], ['Chisom', 'Agu'],
            ['Ayomide', 'Oladele'], ['Nkechi', 'Umeh'], ['Gbenga', 'Fashola'],
            ['Chiamaka', 'Igwe'], ['Olalekan', 'Badmus'], ['Ifeoma', 'Onyeama'],
            ['Rotimi', 'Akinsanya'],
        ];

        $sellers = collect();
        foreach ($sellerNames as $sn) {
            $seller = User::create([
                'name' => "{$sn[0]} {$sn[1]}",
                'email' => strtolower($sn[0]) . '.' . strtolower($sn[1]) . '@example.com',
                'password' => $password,
                'role' => 'seller',
                'is_seller_approved' => true,
                'email_verified_at' => $now->copy()->subDays(rand(30, 120)),
                'created_at' => $now->copy()->subDays(rand(60, 200)),
            ]);
            $sellers->push($seller);
        }

        // ─── 2. SELLER PROFILES ───

        $companies = [
            'Ogechi Research Hub', 'Tobiloba Academic Press', 'Chisom Study Materials',
            'Ayomide Learning Centre', 'Nkechi Knowledge Base', 'Gbenga Scholarly Works',
            'Chiamaka Digital Library', 'Olalekan EduTech', 'Ifeoma Research Network',
            'Rotimi Academic Solutions',
        ];
        $bios = [
            'Passionate about sharing quality academic research materials with students across Nigeria and Africa.',
            'Dedicated to providing affordable and accessible study resources for university students.',
            'A leading provider of well-researched project materials and academic papers.',
            'Committed to academic excellence through curated educational content and resources.',
            'Helping students achieve their academic goals with high-quality project materials.',
            'An experienced researcher providing top-notch academic resources and project guides.',
            'Specializing in engineering and science research papers for undergraduates.',
            'Providing comprehensive study materials for business and management students.',
            'A hub for medical and health science research projects and study guides.',
            'Your one-stop shop for law and social science academic research materials.',
        ];
        $countries = ['Nigeria', 'Nigeria', 'Nigeria', 'Nigeria', 'Nigeria', 'Nigeria', 'Nigeria', 'Nigeria', 'Nigeria', 'Nigeria'];
        $states = ['Lagos', 'Abuja', 'Enugu', 'Ibadan', 'Port Harcourt', 'Benin City', 'Owerri', 'Abeokuta', 'Nsukka', 'Lagos'];

        foreach ($sellers as $i => $seller) {
            SellerProfile::create([
                'user_id' => $seller->id,
                'bio' => $bios[$i],
                'company' => $companies[$i],
                'phone' => '+234' . rand(700, 909) . rand(1000000, 9999999),
                'country' => $countries[$i],
                'region_state' => $states[$i],
                'company_logo' => 'seller-logos/avatar' . rand(1, 3) . '.png',
                'banner' => 'seller-banners/banner' . rand(1, 4) . '.png',
            ]);
        }

        // ─── 3. TAGS ───

        $tagNames = [
            'Research', 'Project', 'Thesis', 'Undergraduate', 'Postgraduate',
            'Literature Review', 'Case Study', 'Survey', 'Analysis', 'Methodology',
            'Data Science', 'Machine Learning', 'Web Development', 'Networking', 'Database',
            'Accounting', 'Marketing', 'Finance', 'Law', 'Education',
        ];
        $tags = collect();
        foreach ($tagNames as $tn) {
            $tags->push(Tag::firstOrCreate(
                ['slug' => Str::slug($tn)],
                ['name' => $tn]
            ));
        }

        // ─── 4. PRODUCTS (50 products across 10 sellers) ───

        $faculties = Faculty::with('departments')->get();
        $documentTypes = ['project', 'thesis', 'seminar_paper', 'research_paper', 'case_study'];
        $classOfDegrees = ['First Class', 'Second Class Upper', 'Second Class Lower', 'Third Class', null];
        $institutions = [
            'University of Lagos', 'University of Nigeria, Nsukka', 'Obafemi Awolowo University',
            'Nnamdi Azikiwe University', 'Ahmadu Bello University', 'University of Ibadan',
            'University of Benin', 'Federal University of Technology Owerri', 'Covenant University',
            'University of Ilorin',
        ];

        $fileTypes = ['pdf', 'zip', 'video'];
        $fileMap = [
            'pdf'   => ['path' => 'products/files/file_pdf.pdf', 'name' => 'file_pdf.pdf', 'size' => 233794, 'type' => 'application/pdf'],
            'video' => ['path' => 'products/files/file_video.mp4', 'name' => 'file_video.mp4', 'size' => 242565, 'type' => 'video/mp4'],
            'zip'   => ['path' => 'products/files/file_zip.zip', 'name' => 'file_zip.zip', 'size' => 211066, 'type' => 'application/zip'],
        ];

        $productTitles = [
            'Impact of Social Media on Academic Performance of University Students',
            'Design and Implementation of a Student Result Management System',
            'Analysis of Financial Reporting Practices in Nigerian Banks',
            'Effect of Motivation on Employee Productivity in the Banking Sector',
            'Machine Learning Approach to Predicting Stock Market Trends',
            'Assessment of Water Quality in Urban Areas of Lagos State',
            'Role of Small and Medium Enterprises in Economic Development',
            'A Study of Customer Satisfaction in the Telecommunications Industry',
            'Implementation of an E-commerce Platform for Agricultural Products',
            'Evaluation of Renewable Energy Sources in Nigeria',
            'Effect of Advertising on Consumer Buying Behaviour',
            'Design of an Automated Attendance System Using Biometrics',
            'Impact of Digital Banking on Financial Inclusion in Nigeria',
            'Assessment of Solid Waste Management in Nigerian Cities',
            'Development of a Library Management System',
            'Role of Corporate Governance in Bank Performance',
            'A Study on Drug Abuse Among Tertiary Institution Students',
            'Analysis of the Impact of Taxation on Small Businesses',
            'Design and Construction of a Solar-Powered Water Heater',
            'Evaluation of Inventory Management Practices in Manufacturing Firms',
            'Impact of Climate Change on Agricultural Productivity',
            'Development of a Hospital Management Information System',
            'Study of Leadership Styles and Organizational Performance',
            'Analysis of Unemployment and Youth Restiveness in Nigeria',
            'Design of an Online Voting System for University Elections',
            'Effect of Foreign Direct Investment on Economic Growth',
            'Implementation of a Chat Application Using WebSockets',
            'Assessment of the Impact of Oil Spills on Marine Ecosystems',
            'Development of a Mobile Learning Application for Secondary Schools',
            'Role of Microfinance Banks in Poverty Alleviation',
            'Object Detection System Using Deep Learning Techniques',
            'Impact of Inflation on Household Savings in Nigeria',
            'Design of an IoT-Based Smart Home Automation System',
            'Study of Antimicrobial Resistance in Clinical Isolates',
            'Effect of Exchange Rate Fluctuations on Import Trade',
            'A Comparative Study of Traditional and Modern Farming Methods',
            'Development of a Point-of-Sale System for Retail Businesses',
            'Analysis of Criminal Justice Administration in Nigeria',
            'Implementation of a Food Delivery Platform',
            'Role of Mass Media in Political Awareness',
            'Design of a Flood Early Warning System Using Sensors',
            'Impact of Entrepreneurship Education on Self-Employment',
            'Study of Nutritional Habits Among University Students',
            'Development of an AI-Powered Chatbot for Customer Support',
            'Effect of Organizational Culture on Employee Retention',
            'Analysis of Public Health Policies for Disease Prevention',
            'Design of a Renewable Energy Monitoring Dashboard',
            'Impact of Social Entrepreneurship on Community Development',
            'Development of an Inventory Tracking System Using RFID',
            'Assessment of Cybersecurity Practices in Nigerian Firms',
        ];

        // Distribute: sellers get 3-8 products each to total ~50
        $distribution = [7, 6, 6, 5, 5, 5, 4, 4, 4, 4]; // = 50
        $productIndex = 0;
        $allProducts = collect();

        foreach ($sellers as $si => $seller) {
            $count = $distribution[$si];
            for ($p = 0; $p < $count; $p++) {
                $faculty = $faculties->random();
                $dept = $faculty->departments->isNotEmpty() ? $faculty->departments->random() : null;
                $ft = $fileTypes[array_rand($fileTypes)];
                $createdAt = $now->copy()->subDays(rand(5, 300));

                $product = Product::create([
                    'user_id' => $seller->id,
                    'faculty_id' => $faculty->id,
                    'department_id' => $dept?->id,
                    'title' => $productTitles[$productIndex],
                    'slug' => Str::slug($productTitles[$productIndex]) . '-' . ($productIndex + 1),
                    'abstract' => $this->generateAbstract($productTitles[$productIndex]),
                    'table_of_content' => "Chapter 1: Introduction\nChapter 2: Literature Review\nChapter 3: Research Methodology\nChapter 4: Data Presentation and Analysis\nChapter 5: Summary, Conclusion and Recommendations\nReferences\nAppendix",
                    'chapter_one' => $this->generateChapterOne($productTitles[$productIndex]),
                    'meta_description' => 'A comprehensive study on ' . strtolower($productTitles[$productIndex]),
                    'document_type' => $documentTypes[array_rand($documentTypes)],
                    'class_of_degree' => $classOfDegrees[array_rand($classOfDegrees)],
                    'institution' => $institutions[array_rand($institutions)],
                    'location_country' => 'Nigeria',
                    'location_region' => $states[array_rand($states)],
                    'date_available' => $createdAt->format('Y-m-d'),
                    'price' => 0,
                    'is_paid' => false,
                    'status' => 'published',
                    'views_count' => rand(10, 2500),
                    'downloads_count' => 0, // will be set from actual downloads
                    'likes_count' => 0, // will be set from actual likes
                    'is_featured' => $productIndex < 5,
                    'published_at' => $createdAt->copy()->addDays(rand(0, 3)),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Product image (1-2 covers)
                $coverCount = rand(1, 2);
                for ($ci = 0; $ci < $coverCount; $ci++) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => 'product-images/cover' . rand(1, 5) . '.png',
                        'sort_order' => $ci,
                    ]);
                }

                // Product file
                $fm = $fileMap[$ft];
                ProductFile::create([
                    'product_id' => $product->id,
                    'file_path' => $fm['path'],
                    'file_name' => $fm['name'],
                    'file_size' => $fm['size'],
                    'file_type' => $fm['type'],
                ]);

                // Tags (2-4 per product)
                $product->tags()->attach($tags->random(rand(2, 4))->pluck('id'));

                // Primary author
                ProductAuthor::create([
                    'product_id' => $product->id,
                    'user_id' => $seller->id,
                    'is_primary' => true,
                    'contribution_percentage' => 100,
                ]);

                $allProducts->push($product);
                $productIndex++;
            }
        }

        // ─── 5. CO-AUTHORS (give ~10 products a co-author from other sellers) ───

        $coAuthorProducts = $allProducts->random(10);
        foreach ($coAuthorProducts as $cap) {
            $coAuthor = $sellers->where('id', '!=', $cap->user_id)->random();

            // Update primary author contribution
            ProductAuthor::where('product_id', $cap->id)
                ->where('is_primary', true)
                ->update(['contribution_percentage' => 70]);

            ProductAuthor::create([
                'product_id' => $cap->id,
                'user_id' => $coAuthor->id,
                'is_primary' => false,
                'contribution_percentage' => 30,
            ]);
        }

        // ─── 6. DOWNLOADS & VIEW REVENUES (from user-role users) ───

        $publishedProducts = $allProducts->where('status', 'published');

        foreach ($users as $user) {
            // Each user downloads 2-8 random products
            $toDownload = $publishedProducts->random(rand(2, 8));
            foreach ($toDownload as $prod) {
                $downloadedAt = Carbon::now()->subDays(rand(0, 180));
                Download::create([
                    'user_id' => $user->id,
                    'product_id' => $prod->id,
                    'ip_address' => rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255),
                    'created_at' => $downloadedAt,
                    'updated_at' => $downloadedAt,
                ]);
            }
        }

        // Update download counts from actual downloads
        foreach ($allProducts as $product) {
            $product->update([
                'downloads_count' => Download::where('product_id', $product->id)->count(),
            ]);
        }

        // Create view revenue entries (spread across the year for chart data)
        foreach ($publishedProducts->random(30) as $product) {
            $viewCount = rand(3, 20);
            for ($v = 0; $v < $viewCount; $v++) {
                $viewedAt = Carbon::now()->subDays(rand(0, 330));
                Revenue::create([
                    'product_id' => $product->id,
                    'user_id' => $product->user_id,
                    'type' => 'view',
                    'amount_usd' => 0,
                    'visitor_ip' => rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255),
                    'created_at' => $viewedAt,
                    'updated_at' => $viewedAt,
                ]);
            }
        }

        // Create download revenue entries too
        foreach ($publishedProducts->random(20) as $product) {
            $dlCount = rand(2, 10);
            for ($d = 0; $d < $dlCount; $d++) {
                $dlAt = Carbon::now()->subDays(rand(0, 330));
                Revenue::create([
                    'product_id' => $product->id,
                    'user_id' => $product->user_id,
                    'type' => 'download',
                    'amount_usd' => 0,
                    'visitor_ip' => rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 255),
                    'created_at' => $dlAt,
                    'updated_at' => $dlAt,
                ]);
            }
        }

        // ─── 7. REVIEWS (from user-role users) ───

        $reviewComments = [
            'Very helpful material for my final year project. Well structured and easy to follow.',
            'Good content but the references section could be more comprehensive.',
            'Excellent research work. Highly recommended for students in this field.',
            'The methodology chapter was particularly useful for my research.',
            'Decent work overall, but some sections need more depth.',
            'Great resource! Saved me a lot of time during my literature review.',
            'Well written and properly formatted. Worth downloading.',
            'The data analysis section is very insightful and well presented.',
            'Could have included more recent references, but still a solid paper.',
            'Fantastic project material. The abstract and chapter one are top-notch.',
            'This was exactly what I needed for my coursework. Thank you!',
            'Average quality - good for getting an overview but lacks detail in places.',
            'Impressive work. The tables and figures are very clear.',
            'Solid research foundation. Helped me understand the topic much better.',
            'I appreciate the thorough literature review in this material.',
        ];

        // Each user reviews 1-4 products
        foreach ($users as $user) {
            $toReview = $publishedProducts->random(min(rand(1, 4), $publishedProducts->count()));
            foreach ($toReview as $product) {
                // Check if already reviewed
                if (Review::where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
                    continue;
                }
                Review::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'rating' => rand(3, 5),
                    'comment' => $reviewComments[array_rand($reviewComments)],
                    'is_approved' => (bool) rand(0, 1),
                    'created_at' => Carbon::now()->subDays(rand(0, 120)),
                ]);
            }
        }

        $this->command->info('Demo data seeded: 20 users, 10 sellers, 50 products, downloads, views, and reviews.');

        // ─── 8. LIKES (from user-role users + sellers) ───

        $allUsers = $users->merge($sellers);

        foreach ($publishedProducts as $product) {
            // Each product gets liked by 0–15 random unique users
            $likerCount = rand(0, 15);
            if ($likerCount === 0) {
                continue;
            }
            $likers = $allUsers->random(min($likerCount, $allUsers->count()));
            foreach ($likers as $liker) {
                if (Like::where('product_id', $product->id)->where('user_id', $liker->id)->exists()) {
                    continue;
                }
                Like::create([
                    'product_id' => $product->id,
                    'user_id'    => $liker->id,
                    'created_at' => Carbon::now()->subDays(rand(0, 180)),
                ]);
            }
        }

        // Sync likes_count with actual likes table count
        foreach ($allProducts as $product) {
            $product->update([
                'likes_count' => Like::where('product_id', $product->id)->count(),
            ]);
        }

        $this->command->info('Likes seeded and likes_count synced from likes table.');
    }

    private function generateAbstract(string $title): string
    {
        return "This study examines the topic of \"{$title}\". "
            . "The research employed a mixed-methods approach combining both quantitative and qualitative data collection techniques. "
            . "Primary data was gathered through structured questionnaires administered to a sample of 150 respondents, while secondary data was obtained from published journals, textbooks, and online databases. "
            . "The findings revealed significant insights into the subject matter, with statistical analysis confirming the research hypotheses. "
            . "The study concludes with actionable recommendations for stakeholders and suggestions for further research in this domain.";
    }

    private function generateChapterOne(string $title): string
    {
        return "<h2>1.1 Background of the Study</h2>"
            . "<p>The topic of \"{$title}\" has gained significant attention in recent years due to its growing relevance in academic and professional discourse. "
            . "Various scholars and researchers have explored different dimensions of this subject, contributing to a rich body of literature that forms the foundation of this study.</p>"
            . "<h2>1.2 Statement of the Problem</h2>"
            . "<p>Despite the extensive research in this area, there remain notable gaps in the understanding of key factors and their implications. "
            . "This study seeks to address these gaps by providing empirical evidence and fresh perspectives on the matter.</p>"
            . "<h2>1.3 Objectives of the Study</h2>"
            . "<p>The primary objective of this study is to investigate the key aspects of \"{$title}\". "
            . "Specific objectives include: (i) to examine the current state of affairs, (ii) to identify the major factors involved, "
            . "(iii) to analyse the relationship between key variables, and (iv) to proffer workable recommendations.</p>"
            . "<h2>1.4 Significance of the Study</h2>"
            . "<p>This study will be beneficial to students, researchers, policymakers, and practitioners who seek to understand this topic in greater depth. "
            . "The findings will also contribute to the existing body of knowledge in the field.</p>";
    }
}

    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-br from-gray-900 to-gray-800 text-white mt-16">
        <!-- Wave Decoration -->
        <div class="relative">
            <svg class="absolute -top-1 w-full h-12 text-gray-50" preserveAspectRatio="none" viewBox="0 0 1440 54">
                <path fill="currentColor" d="M0 22L60 16.7C120 11 240 1.00001 360 0.700012C480 1.00001 600 11 720 16.7C840 22 960 22 1080 19.3C1200 16.7 1320 11 1380 8.30001L1440 5.70001V54H1380C1320 54 1200 54 1080 54C960 54 840 54 720 54C600 54 480 54 360 54C240 54 120 54 60 54H0V22Z"></path>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <!-- Brand -->
                <div class="lg:col-span-1">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-accent-400 rounded-xl flex items-center justify-center">
                            <i class="fas fa-umbrella-beach text-white text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold">เที่ยวไทย</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        <?php echo h(SITE_DESCRIPTION); ?>
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-primary-500 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-pink-500 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-blue-400 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-red-500 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">ลิงก์ด่วน</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="<?php echo BASE_URL; ?>" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-chevron-right text-xs mr-2 text-primary-400"></i>
                                หน้าแรก
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/pages/public/search.php" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-chevron-right text-xs mr-2 text-primary-400"></i>
                                ค้นหาสถานที่
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/pages/public/nearby.php" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-chevron-right text-xs mr-2 text-primary-400"></i>
                                ที่เที่ยวใกล้ฉัน
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-chevron-right text-xs mr-2 text-primary-400"></i>
                                เกี่ยวกับเรา
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">หมวดหมู่ยอดนิยม</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-mountain text-xs mr-2 text-primary-400"></i>
                                ภูเขา/ทิวทัศน์
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-umbrella-beach text-xs mr-2 text-primary-400"></i>
                                ทะเล/ชายหาด
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-torii-gate text-xs mr-2 text-primary-400"></i>
                                วัด/โบราณสถาน
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-white flex items-center transition-colors">
                                <i class="fas fa-coffee text-xs mr-2 text-primary-400"></i>
                                คาเฟ่/ร้านอาหาร
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h3 class="text-lg font-semibold mb-4">ติดต่อเรา</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-primary-400 mt-1 mr-3"></i>
                            <span class="text-gray-400">กรุงเทพมหานคร, ประเทศไทย</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-primary-400 mr-3"></i>
                            <a href="mailto:contact@tourism.com" class="text-gray-400 hover:text-white transition-colors">
                                contact@tourism.com
                            </a>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone text-primary-400 mr-3"></i>
                            <span class="text-gray-400">02-xxx-xxxx</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-700 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm mb-4 md:mb-0">
                        © <?php echo date('Y'); ?> <?php echo h(SITE_NAME); ?>. สงวนลิขสิทธิ์.
                    </p>
                    <div class="flex items-center space-x-4 text-sm text-gray-400">
                        <a href="#" class="hover:text-white transition-colors">นโยบายความเป็นส่วนตัว</a>
                        <span>|</span>
                        <a href="#" class="hover:text-white transition-colors">เงื่อนไขการใช้งาน</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Custom JS -->
    <script src="<?php echo asset('js/app.js'); ?>"></script>

    <!-- Mobile Menu Toggle Script -->
    <script>
        document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
    </body>

    </html>
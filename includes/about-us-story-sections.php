        <?php if ($story_one && (cmsAboutSectionItems($story_one) !== [] || !empty($story_one['content']))): ?>
        <div class="mb-5 ab-cr-bg sm:mt-10 sm:p-4">
            <div class="2xl:w-[1280px] lg:w-[1024px] md:w-[767px] sm:w-[640px] sm:mx-auto">
                <div class="relative about-carousel opne-hide-circle">
                    <?php if (!empty($story_one['content_heading'])): ?>
                        <?= $story_one['content_heading'] ?>
                    <?php endif; ?>
                    <?php if (cmsAboutSectionItems($story_one) !== []): ?>
                    <div class="overflow-hidden mt-5 mx-3" data-glide-el="track">
                        <ul class="mt-5 relative w-full overflow-hidden p-0 whitespace-no-wrap flex flex-no-wrap [backface-visibility: hidden] [transform-style: preserve-3d] [touch-action: pan-Y] [will-change: transform]">
                            <?php foreach (cmsAboutSectionItems($story_one) as $item):
                                $img = cmsAboutAssetUrl($item['image_url'] ?? '');
                                if ($img === '') {
                                    continue;
                                }
                            ?>
                            <li><img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" class="w-full max-w-full m-auto" alt="<?= htmlspecialchars((string) ($item['image_alt'] ?? $item['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="absolute left-0 xl:flex hidden items-center justify-between w-full h-0 px-4 top-[60%] hide-circle" data-glide-el="controls">
                        <button class="inline-flex items-center relative sm:right-[66px] justify-center hover:bg-red-500 hover:text-white w-8 h-8 transition duration-300 border rounded-full lg:w-10 lg:h-10 text-slate-700 border-slate-700 hover:text-slate-900 hover:border-slate-900 focus-visible:outline-none bg-white/20" data-glide-dir="<" aria-label="prev slide"><i class="fa-solid fa-angle-left"></i></button>
                        <button class="inline-flex items-center relative sm:left-[66px] justify-center hover:bg-red-500 hover:text-white w-8 h-8 transition duration-300 border rounded-full lg:w-10 lg:h-10 text-slate-700 border-slate-700 hover:text-slate-900 hover:border-slate-900 focus-visible:outline-none bg-white/20" data-glide-dir=">" aria-label="next slide"><i class="fa-solid fa-angle-right"></i></button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($story_one['content'])): ?>
                <div class="bg-blue-main px-3 mt-[-140px] pt-[160px] pb-5">
                    <div class="text-white ql-snow"><div class="ql-editor"><?= $story_one['content'] ?></div></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($story_two && (cmsAboutSectionItems($story_two) !== [] || !empty($story_two['content']))): ?>
        <div class="2xl:w-[1280px] lg:w-[1024px] md:w-[767px] sm:w-[640px] sm:mx-auto px-3 mb-5">
            <div class="relative about-carousel2 opne-hide-circle">
                <?php if (cmsAboutSectionItems($story_two) !== []): ?>
                <div class="overflow-hidden mt-5" data-glide-el="track">
                    <ul class="mt-5 relative w-full overflow-hidden p-0 whitespace-no-wrap flex flex-no-wrap [backface-visibility: hidden] [transform-style: preserve-3d] [touch-action: pan-Y] [will-change: transform]">
                        <?php foreach (cmsAboutSectionItems($story_two) as $item):
                            $img = cmsAboutAssetUrl($item['image_url'] ?? '');
                            if ($img === '') {
                                continue;
                            }
                        ?>
                        <li><img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" class="w-full max-w-full m-auto" alt="<?= htmlspecialchars((string) ($item['image_alt'] ?? $item['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="absolute left-0 xl:flex hidden items-center justify-between w-full h-0 px-4 top-[60%] hide-circle" data-glide-el="controls">
                    <button class="inline-flex items-center relative sm:right-[66px] justify-center hover:bg-red-500 hover:text-white w-8 h-8 transition duration-300 border rounded-full lg:w-10 lg:h-10 text-slate-700 border-slate-700 hover:text-slate-900 hover:border-slate-900 focus-visible:outline-none bg-white/20" data-glide-dir="<" aria-label="prev slide"><i class="fa-solid fa-angle-left"></i></button>
                    <button class="inline-flex items-center relative sm:left-[66px] justify-center hover:bg-red-500 hover:text-white w-8 h-8 transition duration-300 border rounded-full lg:w-10 lg:h-10 text-slate-700 border-slate-700 hover:text-slate-900 hover:border-slate-900 focus-visible:outline-none bg-white/20" data-glide-dir=">" aria-label="next slide"><i class="fa-solid fa-angle-right"></i></button>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($story_two['content'])): ?>
            <div class="mt-3 text-[18px] text-gray-500 ql-snow"><div class="ql-editor"><?= $story_two['content'] ?></div></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($story_three && (cmsAboutSectionItems($story_three) !== [] || !empty($story_three['content']))): ?>
        <div class="mb-5 ab-cr-bg sm:mt-10 sm:p-4">
            <div class="2xl:w-[1280px] lg:w-[1024px] md:w-[767px] sm:w-[640px] sm:mx-auto">
                <div class="relative glide-last opne-hide-circle">
                    <?php if (!empty($story_three['content_heading'])): ?>
                        <?= $story_three['content_heading'] ?>
                    <?php endif; ?>
                    <?php if (cmsAboutSectionItems($story_three) !== []): ?>
                    <div class="overflow-hidden mt-5 mx-3" data-glide-el="track">
                        <ul class="mt-5 relative w-full overflow-hidden p-0 whitespace-no-wrap flex flex-no-wrap [backface-visibility: hidden] [transform-style: preserve-3d] [touch-action: pan-Y] [will-change: transform]">
                            <?php foreach (cmsAboutSectionItems($story_three) as $item):
                                $img = cmsAboutAssetUrl($item['image_url'] ?? '');
                                if ($img === '') {
                                    continue;
                                }
                            ?>
                            <li><img src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>" class="w-full max-w-full m-auto" alt="<?= htmlspecialchars((string) ($item['image_alt'] ?? $item['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" /></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="absolute left-0 xl:flex hidden items-center justify-between w-full h-0 px-4 top-[60%] hide-circle" data-glide-el="controls">
                        <button class="inline-flex items-center relative sm:right-[66px] justify-center hover:bg-red-500 hover:text-white w-8 h-8 transition duration-300 border rounded-full lg:w-10 lg:h-10 text-slate-700 border-slate-700 hover:text-slate-900 hover:border-slate-900 focus-visible:outline-none bg-white/20" data-glide-dir="<" aria-label="prev slide"><i class="fa-solid fa-angle-left"></i></button>
                        <button class="inline-flex items-center relative sm:left-[66px] justify-center hover:bg-red-500 hover:text-white w-8 h-8 transition duration-300 border rounded-full lg:w-10 lg:h-10 text-slate-700 border-slate-700 hover:text-slate-900 hover:border-slate-900 focus-visible:outline-none bg-white/20" data-glide-dir=">" aria-label="next slide"><i class="fa-solid fa-angle-right"></i></button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($story_three['content'])): ?>
                <div class="bg-blue-main px-3 mt-[-140px] pt-[160px] pb-5 text-center">
                    <div class="text-white ql-snow"><div class="ql-editor"><?= $story_three['content'] ?></div></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

-- 1. 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `auction_system`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 2. 使用这个数据库
USE `auction_system`;

-- （可选）如果你之前已经建过这些表，可以先关闭外键检查再删表
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `UserActivity`;
DROP TABLE IF EXISTS `Recommendation`;
DROP TABLE IF EXISTS `AuctionOutcome`;
DROP TABLE IF EXISTS `Notification`;
DROP TABLE IF EXISTS `Watchlist`;
DROP TABLE IF EXISTS `Bid`;
DROP TABLE IF EXISTS `Auction`;
DROP TABLE IF EXISTS `Category`;
DROP TABLE IF EXISTS `User`;

SET FOREIGN_KEY_CHECKS = 1;

-- 3. 建表

-- 用户表
CREATE TABLE `User` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(100) NOT NULL,
    `username` VARCHAR(100) NOT NULL,
    `role` ENUM('buyer', 'seller') NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 分类表
CREATE TABLE `Category` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) UNIQUE NOT NULL,
    `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 初始化一些分类数据
INSERT INTO `Category` (`category_name`, `description`)
VALUES
('Electronics','Electronic products'),
('Fashion','Clothing & fashion items'),
('Books','All book related items'),
('Sports','Sports equipment and products'),
('Home & garden','Home and gardening supplies');

-- 拍卖表
CREATE TABLE `Auction` (
    `auction_id` INT AUTO_INCREMENT PRIMARY KEY,
    `seller_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `starting_price` DECIMAL(10,2) NOT NULL,
    `reserve_price` DECIMAL(10,2) DEFAULT 0.00,
    `end_time` DATETIME NOT NULL,
    `status` ENUM('active', 'ended', 'cancelled') DEFAULT 'active',
    `img_url` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_auction_seller`
      FOREIGN KEY (`seller_id`) REFERENCES `User`(`user_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_auction_category`
      FOREIGN KEY (`category_id`) REFERENCES `Category`(`category_id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 出价表
CREATE TABLE `Bid` (
    `bid_id` INT AUTO_INCREMENT PRIMARY KEY,
    `auction_id` INT NOT NULL,
    `bidder_id` INT NOT NULL,
    `bid_amount` DECIMAL(10,2) NOT NULL,
    `bid_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `is_active` BOOLEAN DEFAULT TRUE,

    CONSTRAINT `fk_bid_auction`
      FOREIGN KEY (`auction_id`) REFERENCES `Auction`(`auction_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_bid_bidder`
      FOREIGN KEY (`bidder_id`) REFERENCES `User`(`user_id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 关注表
CREATE TABLE `Watchlist` (
    `watchlist_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `auction_id` INT NOT NULL,
    `email_notifications` BOOLEAN DEFAULT TRUE,
    `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_watchlist_user`
      FOREIGN KEY (`user_id`) REFERENCES `User`(`user_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_watchlist_auction`
      FOREIGN KEY (`auction_id`) REFERENCES `Auction`(`auction_id`)
      ON DELETE CASCADE,

    UNIQUE (`user_id`, `auction_id`)   -- 防止重复关注
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 通知表
-- 通知表
CREATE TABLE `Notification` (
    `notification_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,                    -- 接收通知的用户
    `auction_id` INT NOT NULL,                 -- 相关拍卖
    `bid_id` INT NULL,                         -- 相关出价（可为空）
    `type` ENUM(
        'new_bid',
        'outbid',
        'auction_ended',
        'reserve_not_met',
        'winner_confirmed',
        'winner_declined'
    ) NOT NULL,                                -- 通知类型
    `content` VARCHAR(255) NULL,               -- 可选的通知文本
    `is_read` BOOLEAN NOT NULL DEFAULT FALSE,  -- 是否已读
    `email_sent` BOOLEAN NOT NULL DEFAULT FALSE, -- 模拟邮件是否已发送
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, -- 通知创建时间

    CONSTRAINT `fk_notification_user`
      FOREIGN KEY (`user_id`) REFERENCES `User`(`user_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_notification_auction`
      FOREIGN KEY (`auction_id`) REFERENCES `Auction`(`auction_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_notification_bid`
      FOREIGN KEY (`bid_id`) REFERENCES `Bid`(`bid_id`)
      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- 拍卖结果表
CREATE TABLE `AuctionOutcome` (
    `outcome_id` INT AUTO_INCREMENT PRIMARY KEY,
    `auction_id` INT NOT NULL UNIQUE,
    `winner_id` INT NULL,
    `final_price` DECIMAL(10, 2) NULL,
    `reserve_met` BOOLEAN DEFAULT FALSE,
    `seller_accepted` BOOLEAN DEFAULT FALSE,
    `acceptance_deadline` DATETIME NOT NULL,
    `concluded_at` DATETIME NOT NULL,
    `seller_notified` BOOLEAN DEFAULT FALSE,
    `winner_notified` BOOLEAN DEFAULT FALSE,

    CONSTRAINT `fk_outcome_auction`
      FOREIGN KEY (`auction_id`) REFERENCES `Auction`(`auction_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_outcome_winner`
      FOREIGN KEY (`winner_id`) REFERENCES `User`(`user_id`)
      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 推荐表
CREATE TABLE `Recommendation` (
    `rec_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `auction_id` INT NOT NULL,
    `reason` VARCHAR(200) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_recommendation_user`
      FOREIGN KEY (`user_id`) REFERENCES `User`(`user_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_recommendation_auction`
      FOREIGN KEY (`auction_id`) REFERENCES `Auction`(`auction_id`)
      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户行为表
CREATE TABLE `UserActivity` (
    `activity_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `auction_id` INT NULL,
    `action` ENUM('view', 'bid', 'watch', 'search', 'login') NOT NULL,
    `action_time` DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT `fk_activity_user`
      FOREIGN KEY (`user_id`) REFERENCES `User`(`user_id`)
      ON DELETE CASCADE,
    CONSTRAINT `fk_activity_auction`
      FOREIGN KEY (`auction_id`) REFERENCES `Auction`(`auction_id`)
      ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

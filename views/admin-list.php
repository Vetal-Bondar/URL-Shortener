<div class="shortener">
    <h1>БД скорочених посилань</h1>
    
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Оригінальний URL</th>
                <th>Скорочений код</th>
                <th>Кліки</th>
                <th>Дата створення</th>
                <th>Дії</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ( empty( $results ) ) {
                echo '<tr><td colspan="6">Посилань ще немає</td></tr>';
            } else {
                foreach ( $results as $row ) {
                    // Формуємо посилання для видалення
                    $delete_url = wp_nonce_url( 
                        admin_url( 'admin.php?page=url-shortener-page&action=delete&id=' . $row->id ), 
                        'delete_link_' . $row->id 
                    );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $row->id ); ?></td>
                        
                        <td>
                            <a href="<?php echo esc_url( $row->original_url ); ?>" target="_blank">
                                <?php echo esc_html( $row->original_url ); ?>
                            </a>
                        </td>
                        
                        <td><?php echo esc_html( $row->short_code ); ?></td>
                        <td><?php echo esc_html( $row->clicks ); ?></td>
                        <td><?php echo esc_html( $row->created_at ); ?></td>
                        
                        <td>
                            <a href="<?php echo $delete_url; ?>" style="color: red;" onclick="return confirm('Видалити?');">Видалити</a>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
        </tbody>
    </table>
</div>
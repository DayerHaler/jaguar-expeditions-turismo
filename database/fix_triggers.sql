USE jaguar_expeditions;

DELIMITER $$

CREATE TRIGGER actualizar_cupos_reserva 
AFTER INSERT ON reservas 
FOR EACH ROW 
BEGIN
    UPDATE disponibilidad_tours
    SET cupos_reservados = cupos_reservados + NEW.num_clientes
    WHERE tour_id = NEW.tour_id AND fecha = NEW.fecha_tour;
END$$

CREATE TRIGGER restaurar_cupos_cancelacion 
AFTER UPDATE ON reservas 
FOR EACH ROW 
BEGIN
    IF OLD.estado_reserva != 'Cancelada' AND NEW.estado_reserva = 'Cancelada' THEN
        UPDATE disponibilidad_tours
        SET cupos_reservados = cupos_reservados - NEW.num_clientes
        WHERE tour_id = NEW.tour_id AND fecha = NEW.fecha_tour;
    END IF;
END$$

DELIMITER ;

USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebAddObjections]    Script Date: 3/06/2022 2:35:19 PM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
-- =============================================
-- Author:		<Achira Warnakulasuriya>
-- Create date: <5/11/2021>
-- Description:	<Adding values to Objections table and passing APP id and Objections ID to ApplicationObjections table in LOGIC>
-- =============================================
ALTER PROCEDURE [dbo].[spWebAddObjections]
	@OBJ_ObjectorName    VARCHAR(100) = NULL,
	@OBJ_ObjDate         smalldatetime,
	@OBJ_Phone           VARCHAR(15) = NULL,
	@OBJ_Address1        VARCHAR(50) = NULL,
	@OBJ_Address2        VARCHAR(50) = NULL,
	@OBJ_Town            VARCHAR(30) = NULL,
	@OBJ_Postcode        VARCHAR(4) = NULL,
	@OBJ_State           VARCHAR(3) = NULL,
	@OBJ_InterventionOrObjection CHAR(1),
	@OBJ_Notes           VARCHAR(4000) = NULL,
	@OBJ_RC_ID           NUMERIC(18,0) = NULL,
	@OBJ_Email           VARCHAR(255) = NULL,
	@LastUpdateUser      VARCHAR(50) = NULL,
	@LastUpdateDateTime  smalldatetime = NULL,
	@CreationDateTime    smalldatetime = NULL,
	@CreationUser        VARCHAR(50) = NULL,

	@AO_APP_ID NUMERIC(18,0)

AS
BEGIN
	SET NOCOUNT ON;

	DECLARE @obj_id NUMERIC(18,0)

	INSERT INTO Objections (OBJ_ObjectorName, OBJ_ObjDate, OBJ_Phone, OBJ_Address1, OBJ_Address2, OBJ_Town, OBJ_Postcode, OBJ_State, OBJ_InterventionOrObjection,
	OBJ_Notes, OBJ_RC_ID, OBJ_Email, LastUpdateUser, LastUpdateDateTime, CreationDateTime, CreationUser)	
	VALUES
	(@OBJ_ObjectorName, @OBJ_ObjDate, @OBJ_Phone, @OBJ_Address1, @OBJ_Address2, @OBJ_Town, @OBJ_Postcode, @OBJ_State, @OBJ_InterventionOrObjection,
	@OBJ_Notes, @OBJ_RC_ID, @OBJ_Email, @LastUpdateUser, GETDATE(), GETDATE(), @LastUpdateUser)

	SELECT @obj_id = SCOPE_IDENTITY()

	--inserting values to ApplicationObjections
	INSERT INTO ApplicationObjections (AO_APP_ID, CreationDateTime, CreationUser, AO_OBJ_ID)
	VALUES (@AO_APP_ID, GETDATE(), @LastUpdateUser, @obj_id)

	SELECT @obj_id AS OBJ_ID, @AO_APP_ID AS AO_APP_ID 
END
